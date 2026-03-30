<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index;

use Shared\Domain\Search\Index\ElasticConfig;

class ElasticConfigOverride
{
    public static function create(
        string $prefix = 'test-index',
        string $read = 'test-read',
        string $write = 'test-write',
        string $suggestions = 'test-suggestions',
    ): ElasticConfig {
        return new ElasticConfig($prefix, $read, $write, $suggestions);
    }

    public static function default(): ElasticConfig
    {
        return self::create('woopie', 'woopie-read', 'woopie-write', 'woopie-suggestions');
    }
}
