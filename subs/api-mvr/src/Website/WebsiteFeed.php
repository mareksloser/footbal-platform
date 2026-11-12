<?php

declare(strict_types=1);

namespace Mvr\Website;

/** Source of website videos. HTTP in production, an array in tests. */
interface WebsiteFeed
{
    /**
     * @return list<array<string, mixed>> raw feed items
     * @throws \RuntimeException when the feed can't be loaded
     */
    public function items(): array;
}
