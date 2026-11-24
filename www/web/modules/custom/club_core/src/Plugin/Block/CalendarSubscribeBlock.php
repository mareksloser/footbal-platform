<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\club_core\TeamContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Odkazy pro odběr kalendáře (iCal) aktuálního týmu nebo celého klubu.
 */
#[Block(
  id: 'club_calendar_subscribe',
  admin_label: new TranslatableMarkup('Odběr kalendáře (iCal)'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class CalendarSubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private readonly TeamContext $teamContext) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('club_core.team_context'));
  }

  public function build() {
    $team = $this->teamContext->getTeam();
    $slug = $team?->get('field_slug')->value;
    $url = $team && $slug
      ? Url::fromRoute('club_core.ics_team', ['team' => $slug], ['absolute' => TRUE])
      : Url::fromRoute('club_core.ics_all', [], ['absolute' => TRUE]);
    $generated = $url->toString(TRUE);
    $https = $generated->getGeneratedUrl();
    $webcal = (string) preg_replace('#^https?://#', 'webcal://', $https);

    $build = [
      '#theme' => 'club_calendar_subscribe',
      '#ics_url' => $https,
      '#webcal_url' => $webcal,
      '#google_url' => 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($webcal),
      '#team_label' => $team?->label(),
    ];
    $cache = (new CacheableMetadata())
      ->addCacheContexts(['team'])
      ->addCacheTags(['node_list:team'])
      ->addCacheableDependency($generated);
    if ($team) {
      $cache->addCacheableDependency($team);
    }
    $cache->applyTo($build);
    return $build;
  }

}
