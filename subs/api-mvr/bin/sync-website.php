<?php

declare(strict_types=1);

// Cron: */15 * * * * php /path/to/backend/bin/sync-website.php >> /path/to/backend/tmp/sync.log 2>&1

use Mvr\Service\WebsiteSyncService;
use Mvr\Support\Container;

require __DIR__ . '/../src/bootstrap.php';

$container = new Container();
(require __DIR__ . '/../config/services.php')($container);

try {
    $result = $container->get(WebsiteSyncService::class)->sync();
} catch (\Throwable $e) {
    fwrite(STDERR, date('c') . ' ERROR ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo date('c'), " created={$result['created']} updated={$result['updated']} skipped=", count($result['skipped']), PHP_EOL;
foreach ($result['skipped'] as $skip) {
    echo "  - {$skip['ref']}: {$skip['reason']}", PHP_EOL;
}
