<?php

declare(strict_types=1);

namespace Drupal\skbl_core\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\skbl_core\TeamContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Kalendář ve formátu iCalendar (RFC 5545) pro odběr v telefonu.
 *
 * - /kalendar.ics        – celý klub
 * - /kalendar/u15.ics    – zápasy a turnaje týmu + klubové akce
 *
 * Obsahuje zápasy, turnaje a akce od 60 dnů zpět do ~13 měsíců dopředu.
 */
final class IcsController extends ControllerBase {

  private const PAST_DAYS = 60;
  private const FUTURE_DAYS = 400;

  private TeamContext $teamContext;

  public static function create(ContainerInterface $container) {
    $instance = new self();
    $instance->teamContext = $container->get('skbl_core.team_context');
    return $instance;
  }

  public function all(): CacheableResponse {
    return $this->feed(NULL);
  }

  public function team(NodeInterface $team): CacheableResponse {
    return $this->feed($this->teamContext->isClub($team) ? NULL : $team);
  }

  /**
   * Jedna událost (tlačítko „Přidat do kalendáře“ na detailu akce/zápasu).
   */
  public function single(NodeInterface $node): CacheableResponse {
    if (!in_array($node->bundle(), ['match', 'tournament', 'event'], TRUE) || !$node->hasField('field_date') || $node->get('field_date')->isEmpty()) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $cache = (new CacheableMetadata())->addCacheableDependency($node)->addCacheContexts(['url.site']);
    $host = \Drupal::request()->getHost();
    $lines = array_merge(
      ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . $host . '//skbl_core//CS', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'],
      $this->vevent($node, $host, $cache),
      ['END:VCALENDAR'],
    );
    $body = implode("\r\n", array_map([$this, 'fold'], $lines)) . "\r\n";
    $response = new CacheableResponse($body, 200, [
      'Content-Type' => 'text/calendar; charset=utf-8',
      'Content-Disposition' => 'attachment; filename="akce-' . $node->id() . '.ics"',
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

  private function feed(?NodeInterface $team): CacheableResponse {
    $cache = (new CacheableMetadata())
      ->addCacheTags(['node_list:match', 'node_list:event', 'node_list:tournament', 'node_list:team', 'config:system.site'])
      ->addCacheContexts(['url.site'])
      ->setCacheMaxAge(3600);

    $site = (string) $this->config('system.site')->get('name');
    $now = \Drupal::time()->getRequestTime();
    $storage = $this->entityTypeManager()->getStorage('node');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', ['match', 'tournament', 'event'], 'IN')
      ->condition('status', 1)
      ->condition('field_date.end_value', $now - self::PAST_DAYS * 86400, '>=')
      ->condition('field_date.value', $now + self::FUTURE_DAYS * 86400, '<=')
      ->sort('field_date.value', 'ASC');
    if ($team) {
      $ids = [$team->id()];
      if ($club = $this->teamContext->getClub()) {
        $ids[] = $club->id();
      }
      $query->condition('field_team', $ids, 'IN');
      $cache->addCacheableDependency($team);
    }

    $host = \Drupal::request()->getHost();
    $lines = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//' . $host . '//skbl_core//CS',
      'CALSCALE:GREGORIAN',
      'METHOD:PUBLISH',
      'X-WR-CALNAME:' . $this->escape($team ? $site . ' – ' . $team->label() : $site),
      'X-WR-TIMEZONE:' . $this->timezone()->getName(),
      'REFRESH-INTERVAL;VALUE=DURATION:PT6H',
      'X-PUBLISHED-TTL:PT6H',
    ];
    foreach ($storage->loadMultiple($query->execute()) as $node) {
      $cache->addCacheableDependency($node);
      array_push($lines, ...$this->vevent($node, $host, $cache));
    }
    $lines[] = 'END:VCALENDAR';

    $body = implode("\r\n", array_map([$this, 'fold'], $lines)) . "\r\n";
    $filename = $team ? ($this->value($team, 'field_slug') ?? 'tym') : 'skbl';
    $response = new CacheableResponse($body, 200, [
      'Content-Type' => 'text/calendar; charset=utf-8',
      'Content-Disposition' => 'inline; filename="' . $filename . '.ics"',
    ]);
    $response->addCacheableDependency($cache);
    return $response;
  }

  /**
   * Jedna událost VEVENT.
   */
  private function vevent(NodeInterface $node, string $host, CacheableMetadata $cache): array {
    $start = (int) $node->get('field_date')->value;
    $end = (int) ($node->get('field_date')->end_value ?: $start + 7200);
    $tz = $this->timezone();
    $local_start = (new \DateTimeImmutable('@' . $start))->setTimezone($tz);
    $local_end = (new \DateTimeImmutable('@' . $end))->setTimezone($tz);
    $all_day = $local_start->format('H:i') === '00:00' && in_array($local_end->format('H:i'), ['23:59', '00:00'], TRUE);

    $team = $node->hasField('field_team') ? $node->get('field_team')->entity : NULL;
    if ($team) {
      $cache->addCacheableDependency($team);
    }
    $team_prefix = $team && !$this->teamContext->isClub($team)
      ? ($this->value($team, 'field_short_title') ?? $team->label()) . ': '
      : '';

    $status = 'CONFIRMED';
    $location = NULL;
    $description = [];
    switch ($node->bundle()) {
      case 'match':
        $opponent = $this->ref($node, 'field_opponent')?->label() ?? '';
        $us = ($team ? $this->value($team, 'field_display_name') : NULL) ?? 'Libušín';
        $home = $this->value($node, 'field_is_home');
        $is_home = $home === NULL || (bool) $home;
        $summary = $team_prefix . ($is_home ? $us . ' – ' . $opponent : $opponent . ' – ' . $us);
        $match_status = $this->value($node, 'field_match_status');
        $score_us = $this->value($node, 'field_score_us');
        $score_them = $this->value($node, 'field_score_them');
        if ($match_status === 'played' && $score_us !== NULL && $score_them !== NULL) {
          $summary .= ' (' . ($is_home ? $score_us . ':' . $score_them : $score_them . ':' . $score_us) . ')';
        }
        if ($match_status === 'postponed') {
          $summary = 'ODLOŽENO – ' . $summary;
          $status = 'TENTATIVE';
        }
        if ($match_status === 'cancelled') {
          $status = 'CANCELLED';
        }
        $location = $this->ref($node, 'field_venue')?->label() ?? ($is_home ? NULL : $opponent);
        if ($competition = $this->ref($node, 'field_competition')) {
          $description[] = $competition->label();
        }
        $category = 'Zápas';
        break;

      case 'tournament':
        $summary = $team_prefix . $node->label();
        $location = $this->ref($node, 'field_venue')?->label();
        $category = 'Turnaj';
        break;

      default:
        $summary = $team_prefix . $node->label();
        $location = $this->value($node, 'field_location');
        $category = 'Akce';
    }

    $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
    $description[] = $url;

    $lines = [
      'BEGIN:VEVENT',
      'UID:node-' . $node->id() . '@' . $host,
      'DTSTAMP:' . gmdate('Ymd\THis\Z', (int) $node->getChangedTime()),
      'LAST-MODIFIED:' . gmdate('Ymd\THis\Z', (int) $node->getChangedTime()),
    ];
    if ($all_day) {
      $last_day = $local_end->format('H:i') === '00:00' ? $local_end : $local_end->modify('+1 day');
      $lines[] = 'DTSTART;VALUE=DATE:' . $local_start->format('Ymd');
      $lines[] = 'DTEND;VALUE=DATE:' . $last_day->format('Ymd');
    }
    else {
      $lines[] = 'DTSTART:' . gmdate('Ymd\THis\Z', $start);
      $lines[] = 'DTEND:' . gmdate('Ymd\THis\Z', $end);
    }
    $lines[] = 'SUMMARY:' . $this->escape($summary);
    if ($location) {
      $lines[] = 'LOCATION:' . $this->escape($location);
    }
    $lines[] = 'DESCRIPTION:' . $this->escape(implode("\n", $description));
    $lines[] = 'URL:' . $url;
    $lines[] = 'CATEGORIES:' . $category;
    $lines[] = 'STATUS:' . $status;
    $lines[] = 'END:VEVENT';
    return $lines;
  }

  private function timezone(): \DateTimeZone {
    return new \DateTimeZone((string) ($this->config('system.date')->get('timezone.default') ?: 'Europe/Prague'));
  }

  /**
   * Escapování textu podle RFC 5545.
   */
  private function escape(string $text): string {
    return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $text);
  }

  /**
   * Zalomení řádků na 75 bajtů bez rozdělení UTF-8 znaku.
   */
  private function fold(string $line): string {
    $out = '';
    $limit = 75;
    while (strlen($line) > $limit) {
      $chunk = mb_strcut($line, 0, $limit, 'UTF-8');
      $out .= $chunk . "\r\n ";
      $line = substr($line, strlen($chunk));
      $limit = 74;
    }
    return $out . $line;
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
