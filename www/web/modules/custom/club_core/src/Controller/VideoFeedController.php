<?php

declare(strict_types=1);

namespace Drupal\club_core\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Seznam videí z videotéky pro aplikaci Match Video Review (MVR).
 *
 * GET /api/mvr/videos.json[?since=<unix|ISO 8601>]
 *
 * Vrací jen videa s přímým souborem (.mp4/.webm/.m4v), tj. ta nahraná
 * na FTP. Odkazy na VEO se vynechávají – MVR je nepotřebuje přebírat.
 *
 * Nastavení v settings.php:
 *   $settings['club_mvr_feed_token'] = 'dlouhy-nahodny-retezec';
 *     – pokud je nastaven, vyžaduje hlavičku „X-Feed-Token“ (nebo ?token=).
 *   $settings['club_mvr_video_hosts'] = ['example.cz', 'video.example.cz'];
 *     – volitelně omezí videa na soubory z těchto domén.
 */
final class VideoFeedController extends ControllerBase {

  private const FILE_PATTERN = '/\.(mp4|webm|m4v)(\?|#|$)/i';

  public function feed(Request $request): JsonResponse {
    $token = (string) Settings::get('club_mvr_feed_token', '');
    // Internal Page Cache rozlišuje jen URL, ne hlavičku s tokenem.
    if ($token !== '') {
      \Drupal::service('page_cache_kill_switch')->trigger();
    }
    $given = (string) ($request->headers->get('X-Feed-Token') ?? $request->query->get('token', ''));
    if ($token !== '' && !hash_equals($token, $given)) {
      return new JsonResponse(['error' => 'Invalid feed token'], 403);
    }

    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:video', 'node_list:match', 'node_list:team'])
      ->addCacheContexts(['url.query_args:since', 'url.site', 'headers:X-Feed-Token', 'url.query_args:token']);

    $storage = $this->entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'video')
      ->condition('status', 1)
      ->sort('changed', 'ASC');
    if ($since = $this->parseSince((string) $request->query->get('since', ''))) {
      $query->condition('changed', $since, '>=');
    }

    $hosts = (array) Settings::get('club_mvr_video_hosts', []);
    $items = [];
    foreach ($storage->loadMultiple($query->execute()) as $video) {
      $cache->addCacheableDependency($video);
      $url = $this->videoUrl($video);
      if (!$url || !preg_match(self::FILE_PATTERN, $url)) {
        continue;
      }
      if ($hosts && !in_array(parse_url($url, PHP_URL_HOST), $hosts, TRUE)) {
        continue;
      }
      $items[] = $this->item($video, $url, $cache);
    }

    $response = new CacheableJsonResponse([
      'generated' => date('c', \Drupal::time()->getRequestTime()),
      'count' => count($items),
      'items' => $items,
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

  private function item(NodeInterface $video, string $url, CacheableMetadata $cache): array {
    $teams = [];
    foreach ($video->hasField('field_team') ? $video->get('field_team')->referencedEntities() : [] as $team) {
      $cache->addCacheableDependency($team);
      if ($slug = $this->value($team, 'field_slug')) {
        $teams[] = $slug;
      }
    }

    $thumbnail = NULL;
    if ($video->hasField('field_thumbnail') && ($file = $video->get('field_thumbnail')->entity)) {
      $thumbnail = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
    }

    $match = $this->ref($video, 'field_match');
    return [
      'ref' => 'club:node:' . $video->id(),
      'id' => (int) $video->id(),
      'title' => $video->label(),
      'video_url' => $url,
      'duration' => $this->value($video, 'field_duration'),
      'category' => $this->ref($video, 'field_video_category')?->label(),
      'teams' => $teams,
      'created' => date('c', (int) $video->getCreatedTime()),
      'changed' => date('c', (int) $video->getChangedTime()),
      'web_url' => $video->toUrl('canonical', ['absolute' => TRUE])->toString(),
      'thumbnail_url' => $thumbnail,
      'match' => $match instanceof NodeInterface ? $this->match($match, $cache) : NULL,
    ];
  }

  private function match(NodeInterface $match, CacheableMetadata $cache): array {
    $cache->addCacheableDependency($match);
    $team = $this->ref($match, 'field_team');
    $home = $this->value($match, 'field_is_home');
    $is_home = $home === NULL || (bool) $home;
    $us = $this->value($match, 'field_score_us');
    $them = $this->value($match, 'field_score_them');
    $start = $match->hasField('field_date') && !$match->get('field_date')->isEmpty()
      ? (int) $match->get('field_date')->value
      : NULL;

    return [
      'ref' => 'club:node:' . $match->id(),
      'id' => (int) $match->id(),
      'team' => $team ? $this->value($team, 'field_slug') : NULL,
      'date' => $start ? date('c', $start) : NULL,
      'opponent' => $this->ref($match, 'field_opponent')?->label(),
      'is_home' => $is_home,
      'score_us' => $us !== NULL ? (int) $us : NULL,
      'score_them' => $them !== NULL ? (int) $them : NULL,
      'competition' => $this->ref($match, 'field_competition')?->label(),
      'web_url' => $match->toUrl('canonical', ['absolute' => TRUE])->toString(),
    ];
  }

  private function videoUrl(NodeInterface $video): ?string {
    if (!$video->hasField('field_video_url') || $video->get('field_video_url')->isEmpty()) {
      return NULL;
    }
    return $video->get('field_video_url')->first()->getUrl()->setAbsolute()->toString();
  }

  private function parseSince(string $since): ?int {
    if ($since === '') {
      return NULL;
    }
    if (ctype_digit($since)) {
      return (int) $since;
    }
    $time = strtotime($since);
    return $time === FALSE ? NULL : $time;
  }

  private function value(?FieldableEntityInterface $entity, string $field): ?string {
    if (!$entity || !$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    return (string) $entity->get($field)->value;
  }

  private function ref(?FieldableEntityInterface $entity, string $field): ?FieldableEntityInterface {
    if (!$entity || !$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return NULL;
    }
    $target = $entity->get($field)->entity;
    return $target instanceof FieldableEntityInterface ? $target : NULL;
  }

}
