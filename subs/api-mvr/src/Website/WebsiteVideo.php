<?php

declare(strict_types=1);

namespace Mvr\Website;

/** One video from the club website feed (GET /api/mvr/videos.json). */
final class WebsiteVideo
{
    /** @param list<string> $teams team slugs, e.g. ["u15"] */
    public function __construct(
        public readonly string $ref,
        public readonly string $title,
        public readonly string $videoUrl,
        public readonly array $teams,
        public readonly string $matchDate,
        public readonly ?string $webUrl,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @throws \InvalidArgumentException when the item is unusable
     */
    public static function fromArray(array $item, string $timezone = 'Europe/Prague'): self
    {
        $ref = (string) ($item['ref'] ?? '');
        $title = trim((string) ($item['title'] ?? ''));
        $url = trim((string) ($item['video_url'] ?? ''));
        if (!preg_match('/^[a-z]+:[a-z]+:\d+$/', $ref) || $title === '' || $url === '') {
            throw new \InvalidArgumentException('Neúplná položka feedu.');
        }

        // Datum zápasu, jinak datum vytvoření videa – v časové zóně klubu.
        $iso = (string) (($item['match']['date'] ?? null) ?: ($item['created'] ?? ''));
        try {
            $date = (new \DateTimeImmutable($iso !== '' ? $iso : 'now'))
                ->setTimezone(new \DateTimeZone($timezone))
                ->format('Y-m-d');
        } catch (\Exception) {
            throw new \InvalidArgumentException("Neplatné datum u $ref.");
        }

        $teams = array_values(array_filter(
            array_map('strval', (array) ($item['teams'] ?? [])),
            static fn (string $s): bool => $s !== '',
        ));
        if (!$teams && !empty($item['match']['team'])) {
            $teams = [(string) $item['match']['team']];
        }

        return new self(
            $ref,
            mb_substr($title, 0, 255),
            $url,
            $teams,
            $date,
            isset($item['web_url']) ? (string) $item['web_url'] : null,
        );
    }
}
