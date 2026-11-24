<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\club_core\TeamContext;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lišta čipů pro výběr týmu.
 *
 * Na stránkách node (tým, zápas, hráč…) odkazuje na stránku týmu,
 * na ostatních stránkách (Views jako /videoteka) přidá ?tym=<slug>.
 */
#[Block(
  id: 'club_team_switcher',
  admin_label: new TranslatableMarkup('Přepínač týmů'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class TeamSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly TeamContext $teamContext,
    private readonly RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('club_core.team_context'),
      $container->get('current_route_match'),
    );
  }

  public function build() {
    $cache = (new CacheableMetadata())
      ->addCacheContexts(['team', 'route', 'url.query_args:' . TeamContext::QUERY_KEY])
      ->addCacheTags(['node_list:team']);

    $current = $this->teamContext->getTeam();
    $route = $this->routeMatch->getRouteName();
    $linkToNodes = $route === 'entity.node.canonical';
    $linkToDetail = $route === 'club_core.team_detail';
    $teamsPage = $linkToDetail ? \Drupal::pathValidator()->getUrlIfValid('/tymy') : NULL;
    $items = [];

    foreach ($this->teamContext->getTeams() as $team) {
      $isClub = $this->teamContext->isClub($team);
      $slug = (string) $team->get('field_slug')->value;

      if ($linkToDetail) {
        $url = $isClub
          ? ($teamsPage ?: Url::fromRoute('<front>'))
          : Url::fromRoute('club_core.team_detail', ['node' => $slug]);
      }
      elseif ($linkToNodes) {
        $url = $isClub ? Url::fromRoute('<front>') : $team->toUrl();
      }
      else {
        $url = Url::fromRoute('<current>', [], [
          'query' => $isClub ? [] : [TeamContext::QUERY_KEY => $slug],
        ]);
      }

      $generated = $url->toString(TRUE);
      $cache->addCacheableDependency($generated)->addCacheableDependency($team);

      $short = $team->hasField('field_short_title') ? $team->get('field_short_title')->value : NULL;
      $items[] = [
        'label' => $short ?: $team->label(),
        'url' => $generated->getGeneratedUrl(),
        'active' => $isClub ? $current === NULL : $current?->id() === $team->id(),
        'merged' => $this->teamContext->isMerged($team),
        'slug' => $slug,
      ];
    }

    $build = [
      '#theme' => 'club_team_switcher',
      '#items' => $items,
    ];
    $cache->applyTo($build);
    return $build;
  }

}
