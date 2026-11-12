<?php

declare(strict_types=1);

namespace Mvr\Service;

use Mvr\Auth\AccessPolicy;
use Mvr\Domain\MatchData;
use Mvr\Domain\User;
use Mvr\Repository\CategoryRepository;
use Mvr\Repository\MatchRepository;
use Mvr\Support\Clock;
use Mvr\Video\VideoSourceRegistry;
use Mvr\Website\WebsiteFeed;
use Mvr\Website\WebsiteVideo;

/**
 * Takes videos uploaded to the club website (own files, not VEO) into MVR.
 *
 * - new video   → new match (not public; the coach decides)
 * - known video → updates title, date, team and URL; is_public, markers,
 *                 drawings and reviews are never touched
 * - a video removed from the website is NOT deleted here (coaches' work stays)
 */
final class WebsiteSyncService
{
    public function __construct(
        private readonly WebsiteFeed $feed,
        private readonly MatchRepository $matches,
        private readonly CategoryRepository $categories,
        private readonly VideoSourceRegistry $sources,
        private readonly AccessPolicy $policy,
        private readonly Clock $clock,
        private readonly string $timezone = 'Europe/Prague',
    ) {
    }

    /** From the API: coaches only. */
    public function syncAs(?User $viewer): array
    {
        $this->policy->requireAdmin($viewer);
        return $this->sync();
    }

    /**
     * From cron / CLI.
     * @return array{created: int, updated: int, skipped: list<array{ref: string, reason: string}>}
     */
    public function sync(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => []];
        $now = $this->clock->now()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        foreach ($this->feed->items() as $item) {
            $ref = (string) ($item['ref'] ?? '?');
            try {
                $video = WebsiteVideo::fromArray($item, $this->timezone);
                $categoryId = $this->categoryFor($video);
                if ($categoryId === null) {
                    $result['skipped'][] = ['ref' => $ref, 'reason' => 'Tým ' . ($video->teams ? implode(', ', $video->teams) : '(žádný)') . ' nemá v MVR kategorii se stejným slugem.'];
                    continue;
                }
                $normalized = $this->sources->normalize('auto', $video->videoUrl);
                $existing = $this->matches->findIdByExternalRef($video->ref);

                $data = new MatchData(
                    $video->title,
                    $video->matchDate,
                    $categoryId,
                    $normalized->type,
                    $normalized->url,
                    false, // ignored on update, see updateExternal()
                );
                if ($existing === null) {
                    $this->matches->createExternal($data, $video->ref, $video->webUrl, $now);
                    $result['created']++;
                }
                else {
                    $this->matches->updateExternal($existing, $data, $video->webUrl, $now);
                    $result['updated']++;
                }
            } catch (\InvalidArgumentException $e) {
                $result['skipped'][] = ['ref' => $ref, 'reason' => $e->getMessage()];
            }
        }
        return $result;
    }

    private function categoryFor(WebsiteVideo $video): ?int
    {
        foreach ($video->teams as $slug) {
            if (($id = $this->categories->idBySlug($slug)) !== null) {
                return $id;
            }
        }
        return null;
    }
}
