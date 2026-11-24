<?php

declare(strict_types=1);

namespace Drupal\club_core;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Sestaví kontakty z realizačních týmů (field_staff) všech týmů.
 *
 * - Realizační tým nodu „Celý klub“ = vedení klubu, rozdělené do skupin
 *   podle pole Skupina (taxonomie contact_group: Vedení klubu, Výkonný výbor…).
 *   Člen bez skupiny patří do „Vedení klubu“.
 * - Realizační týmy ostatních týmů = Trenéři a vedoucí týmů.
 *
 * Člen realizačního týmu (paragraph staff_member) má roli a odkaz
 * na Osobu (node person) s telefonem a e-mailem. Osobu zadáte jednou,
 * i když má víc funkcí (předseda + trenér dorostu).
 */
final class ContactsBuilder {

  public function __construct(private readonly TeamContext $teamContext) {}

  public function build(): array {
    $cache = (new CacheableMetadata())->addCacheTags(['node_list:team', 'node_list:person', 'taxonomy_term_list:contact_group']);
    $sections = [];
    $teams = [];

    foreach ($this->teamContext->getTeams() as $team) {
      $cache->addCacheableDependency($team);
      if (!$team->hasField('field_staff')) {
        continue;
      }
      $is_club = $this->teamContext->isClub($team);
      $short = $team->hasField('field_short_title') ? $team->get('field_short_title')->value : NULL;

      foreach ($team->get('field_staff')->referencedEntities() as $member) {
        $cache->addCacheableDependency($member);
        $person = $member->hasField('field_person') ? $member->get('field_person')->entity : NULL;
        if ($person) {
          $cache->addCacheableDependency($person);
          if (!$person->access('view')) {
            continue;
          }
        }
        $phone = $this->value($person, 'field_phone');
        $contact = [
          'role' => $this->value($member, 'field_role'),
          'name' => $person?->label() ?? $this->value($member, 'field_person_name'),
          'phone' => $phone ? self::formatPhone($phone) : NULL,
          'phone_href' => $phone ? preg_replace('/[^\d+]/', '', $phone) : NULL,
          'email' => $this->value($person, 'field_email') ?? $this->value($member, 'field_email'),
          'team' => $is_club ? NULL : $team->label(),
          'team_short' => $short ?: $team->label(),
          'merged' => $this->teamContext->isMerged($team),
        ];
        if (!$contact['name']) {
          continue;
        }
        if ($is_club) {
          $group = $member->hasField('field_contact_group') ? $member->get('field_contact_group')->entity : NULL;
          if ($group) {
            $cache->addCacheableDependency($group);
          }
          $key = $group ? 'g' . $group->id() : 'default';
          $sections[$key] ??= [
            'group_id' => $group?->id(),
            'title' => $group?->label() ?? 'Vedení klubu',
            'weight' => $group && method_exists($group, 'getWeight') ? (int) $group->getWeight() : -1000,
            'items' => [],
          ];
          $sections[$key]['items'][] = $contact;
        }
        else {
          $teams[] = $contact;
        }
      }
    }

    uasort($sections, static fn($a, $b) => [$a['weight'], $a['title']] <=> [$b['weight'], $b['title']]);

    $build = [
      '#theme' => 'club_contacts',
      '#sections' => array_values($sections),
      '#teams' => $teams,
    ];
    $cache->applyTo($build);
    return $build;
  }

  /**
   * Český formát telefonu: 608029915 → 608 029 915, +420608029915 → +420 608 029 915.
   * Jiné tvary (zahraniční, s pobočkou) nechá, jak byly zadané.
   */
  public static function formatPhone(string $phone): string {
    $digits = preg_replace('/[^\d+]/', '', $phone);
    if (preg_match('/^(\+420)?(\d{3})(\d{3})(\d{3})$/', $digits, $m)) {
      return trim(($m[1] ? $m[1] . ' ' : '') . $m[2] . ' ' . $m[3] . ' ' . $m[4]);
    }
    return $phone;
  }

  private function value(?FieldableEntityInterface $entity, string $field): ?string {
    if (!$entity || !$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    return (string) $entity->get($field)->value;
  }

}
