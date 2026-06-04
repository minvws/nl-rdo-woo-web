<?php

declare(strict_types=1);

namespace Shared\Tests;

use Shared\Domain\Search\Index\ElasticConfig;

class ElasticConfigFactory
{
    public static function default(): ElasticConfig
    {
        return new ElasticConfig('woopie', 'woopie-read', 'woopie-write', 'woopie-suggestions', 'worker_stats');
    }
}
