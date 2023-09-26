<?php

declare(strict_types=1);

namespace App\Service\Stats;

use App\Service\Stats\Handler\StatsHandlerInterface;

class WorkerStatsService
{
    /** @var array|StatsHandlerInterface[] */
    protected array $handlers;

    /**
     * @param StatsHandlerInterface[] $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * @param mixed[] $args
     */
    public function measure(string $section, callable $f, array $args = []): mixed
    {
        $dt = new \DateTimeImmutable();
        $hostname = strval(gethostname());

        $timeStart = (microtime(true) * 1000);
        $result = call_user_func_array($f, $args);
        $duration = (int) ((microtime(true) * 1000) - $timeStart);

        foreach ($this->handlers as $handler) {
            $handler->store($dt, $hostname, $section, $duration);
        }

        return $result;
    }
}
