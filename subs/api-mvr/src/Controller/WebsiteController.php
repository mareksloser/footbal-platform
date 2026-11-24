<?php

declare(strict_types=1);

namespace Mvr\Controller;

use Mvr\Auth\Authenticator;
use Mvr\Http\HttpException;
use Mvr\Http\Request;
use Mvr\Http\Response;
use Mvr\Repository\MatchRepository;
use Mvr\Service\MatchService;
use Mvr\Service\WebsiteSyncService;

/** Link to the club website: video sync and lookup by the website's video id. */
final class WebsiteController
{
    public function __construct(
        private readonly Authenticator $auth,
        private readonly WebsiteSyncService $sync,
        private readonly MatchRepository $matches,
        private readonly MatchService $matchService,
        private readonly string $cronToken = '',
    ) {
    }

    /**
     * GET /cron/sync-website?token=… – for hostings whose cron can only call a URL.
     * Protected by CRON_TOKEN from .env (no token configured = endpoint disabled).
     */
    public function cron(Request $request): Response
    {
        $given = (string) ($request->query['token'] ?? $request->header('x-cron-token') ?? '');
        if ($this->cronToken === '' || !hash_equals($this->cronToken, $given)) {
            throw HttpException::notFound('Neznámý endpoint.');
        }
        @set_time_limit(300);
        try {
            $result = $this->sync->sync();
        } catch (\RuntimeException $e) {
            throw new HttpException($e->getMessage(), 502);
        }
        return Response::json(['ok' => true, 'at' => date('c')] + $result);
    }

    /** POST /sync/website – coach button „Načíst videa z webu“. */
    public function sync(Request $request): Response
    {
        try {
            return Response::json($this->sync->syncAs($this->auth->user($request)));
        } catch (\RuntimeException $e) {
            throw new HttpException($e->getMessage(), 502);
        }
    }

    /** GET /matches/by-ref?ref=club:node:123 – target of the „Rozbor v MVR“ link on the website. */
    public function byRef(Request $request): Response
    {
        $ref = (string) ($request->query['ref'] ?? '');
        $id = $ref !== '' ? $this->matches->findIdByExternalRef($ref) : null;
        if ($id === null) {
            throw HttpException::notFound('Video z webu v MVR zatím není.');
        }
        // Same visibility rules as everywhere else (404 when the viewer can't see it).
        $this->matchService->visibleMatch($this->auth->user($request), $id);
        return Response::json(['id' => $id]);
    }
}
