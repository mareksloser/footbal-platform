<?php

declare(strict_types=1);

namespace Drupal\club_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Určuje „aktuální tým“ stránky.
 *
 * Pořadí:
 *  1. node typu Tým na route (stránka týmu, úvodní stránka = „Celý klub“),
 *  2. field_team z node na route (zápas, hráč, aktualita…),
 *  3. query parametr ?tym=<slug> (výpisové stránky jako /videoteka),
 *  4. nic = celý klub (vrací NULL).
 */
final class TeamContext {

  public const BUNDLE = 'team';
  public const QUERY_KEY = 'tym';

  /** @var array<int, \Drupal\node\NodeInterface|null> Výsledek pro každý request. */
  private array $resolved = [];

  /** @var array<string, int|null> Slug => nid. */
  private array $slugMap = [];

  private ?int $clubId = NULL;

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly RequestStack $requestStack,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Aktuální tým, nebo NULL pro „Celý klub“.
   */
  public function getTeam(): ?NodeInterface {
    $request = $this->requestStack->getCurrentRequest();
    $key = $request ? spl_object_id($request) : 0;
    if (array_key_exists($key, $this->resolved)) {
      return $this->resolved[$key];
    }
    return $this->resolved[$key] = $this->resolve();
  }

  /**
   * Slug aktuálního týmu, „klub“ pro celý klub. Používá cache context.
   */
  public function getSlug(): string {
    return $this->getTeam()?->get('field_slug')->value ?? 'klub';
  }

  /**
   * Node „Celý klub“ (tým se zaškrtnutým field_is_club).
   */
  public function getClub(): ?NodeInterface {
    if ($this->clubId === NULL) {
      $ids = $this->nodeQuery()->condition('field_is_club', 1)->range(0, 1)->execute();
      $this->clubId = $ids ? (int) reset($ids) : 0;
    }
    return $this->clubId ? $this->nodeStorage()->load($this->clubId) : NULL;
  }

  /**
   * Všechny publikované týmy seřazené podle váhy (včetně „Celý klub“).
   *
   * @return \Drupal\node\NodeInterface[]
   */
  public function getTeams(): array {
    $ids = $this->nodeQuery()
      ->sort('field_weight', 'ASC')
      ->sort('title', 'ASC')
      ->execute();
    return $this->nodeStorage()->loadMultiple($ids);
  }

  public function loadBySlug(string $slug): ?NodeInterface {
    if (!array_key_exists($slug, $this->slugMap)) {
      $ids = $this->nodeQuery()->condition('field_slug', $slug)->range(0, 1)->execute();
      $this->slugMap[$slug] = $ids ? (int) reset($ids) : NULL;
    }
    $id = $this->slugMap[$slug];
    return $id ? $this->nodeStorage()->load($id) : NULL;
  }

  public function isClub(NodeInterface $node): bool {
    return $node->bundle() === self::BUNDLE
      && $node->hasField('field_is_club')
      && (bool) $node->get('field_is_club')->value;
  }

  /**
   * Je tým společný s jiným klubem (partnerským klubem)?
   */
  public function isMerged(NodeInterface $team): bool {
    return $team->hasField('field_partner_club') && !$team->get('field_partner_club')->isEmpty();
  }

  private function resolve(): ?NodeInterface {
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof NodeInterface) {
      if ($node->bundle() === self::BUNDLE) {
        return $this->isClub($node) ? NULL : $node;
      }
      if ($node->hasField('field_team') && !$node->get('field_team')->isEmpty()) {
        $team = $node->get('field_team')->entity;
        if ($team instanceof NodeInterface && !$this->isClub($team)) {
          return $team;
        }
      }
    }

    $slug = $this->requestStack->getCurrentRequest()?->query->get(self::QUERY_KEY);
    if (is_string($slug) && preg_match('/^[a-z0-9-]+$/', $slug)) {
      $team = $this->loadBySlug($slug);
      if ($team && !$this->isClub($team)) {
        return $team;
      }
    }

    return NULL;
  }

  private function nodeStorage() {
    return $this->entityTypeManager->getStorage('node');
  }

  private function nodeQuery() {
    return $this->nodeStorage()->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', self::BUNDLE)
      ->condition('status', NodeInterface::PUBLISHED);
  }

}
