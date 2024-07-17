<?php

declare(strict_types=1);

namespace App\Service\Stats;

use App\Service\Stats\Handler\StatsHandlerInterface;
use Carbon\CarbonImmutable;
use Webmozart\Assert\Assert;

class WorkerStatsService
{
    /**
     * @param array<array-key,StatsHandlerInterface> $handlers
     */
    public function __construct(protected array $handlers)
    {
    }

    /**
     * @param array<array-key,mixed> $args
     */
    public function measure(string $section, callable $f, array $args = []): mixed
    {
        $date = new CarbonImmutable();
        $hostname = gethostname();

        Assert::notFalse($hostname, 'Failed to get hostname');

        $timeStart = microtime(true) * 1000;
        $result = call_user_func_array($f, $args);
        $duration = (int) ((microtime(true) * 1000) - $timeStart);

        foreach ($this->handlers as $handler) {
            $handler->store($date, $hostname, $section, $duration);
        }

        return $result;
    }
}
