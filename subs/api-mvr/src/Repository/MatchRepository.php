<?php

declare(strict_types=1);

namespace Mvr\Repository;

use Mvr\Domain\MatchData;
use Mvr\Domain\MatchStats;
use Mvr\Domain\MatchVideo;

interface MatchRepository
{
    public function find(int $id): ?MatchVideo;

    /** @return list<MatchVideo> newest first */
    public function list(?int $categoryId): array;

    /**
     * Marker counts per match; viewedCount is computed for $viewerId when given.
     * @param list<int> $matchIds
     * @return array<int, MatchStats>
     */
    public function stats(array $matchIds, ?int $viewerId): array;

    public function create(MatchData $data): int;

    public function update(int $id, MatchData $data): void;

    public function delete(int $id): void;

    /** Match created from a website video (matches.external_ref), or null. */
    public function findIdByExternalRef(string $ref): ?int;

    /** New match from a website video; is_public = $data->isPublic. */
    public function createExternal(MatchData $data, string $ref, ?string $webUrl, string $syncedAt): int;

    /** Website-owned fields only (title, date, team, video). is_public stays as set in MVR. */
    public function updateExternal(int $id, MatchData $data, ?string $webUrl, string $syncedAt): void;
}
