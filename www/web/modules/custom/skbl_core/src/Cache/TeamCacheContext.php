<?php

declare(strict_types=1);

namespace Drupal\skbl_core\Cache;

use Drupal\skbl_core\TeamContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Cache context „team“ – varianta podle aktuálního týmu.
 *
 * Použití: '#cache' => ['contexts' => ['team']].
 */
final class TeamCacheContext implements CacheContextInterface {

  public function __construct(private readonly TeamContext $teamContext) {}

  public static function getLabel() {
    return t('Aktuální tým');
  }

  public function getContext() {
    return $this->teamContext->getSlug();
  }

  public function getCacheableMetadata() {
    // Změna slugu nebo publikace týmu musí zneplatnit variace.
    return (new CacheableMetadata())->addCacheTags(['node_list:team']);
  }

}
