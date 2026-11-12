<?php

declare(strict_types=1);

namespace Mvr\Website;

/** Loads the website feed over HTTPS with the shared token (header X-Feed-Token). */
final class HttpWebsiteFeed implements WebsiteFeed
{
    public function __construct(
        private readonly string $url,
        private readonly string $token,
        private readonly int $timeout = 20,
    ) {
    }

    public function items(): array
    {
        if ($this->url === '') {
            throw new \RuntimeException('Není nastaven WEBSITE_FEED_URL.');
        }
        $headers = ['Accept: application/json'];
        if ($this->token !== '') {
            $headers[] = 'X-Feed-Token: ' . $this->token;
        }
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => $this->timeout,
            'ignore_errors' => true,
        ]]);

        $body = @file_get_contents($this->url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1];
            }
        }
        if ($body === false || $status !== 200) {
            throw new \RuntimeException("Feed webu se nepodařilo načíst (HTTP $status).");
        }
        try {
            $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \RuntimeException('Feed webu nevrátil platný JSON.');
        }
        return array_values(array_filter((array) ($data['items'] ?? []), 'is_array'));
    }
}
