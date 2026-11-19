<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\App;

return App::config([
    'framework' => [
        'cache' => [
            'pools' => [
                'content_extract_cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'provider' => 'snc_redis.ingest',
                ],
                'metadata_extract_cache' => [
                    'adapter' => 'cache.adapter.redis',
                    'provider' => 'snc_redis.ingest',
                ],
            ],
        ],
    ],
]);
