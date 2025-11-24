<?php

declare(strict_types=1);

namespace Shared\Service\Stats;

use Carbon\CarbonImmutable;
use Shared\Service\Stats\Handler\StatsHandlerInterface;
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
        $date = CarbonImmutable::now();
        $hostname = $this->getHostname();

        $timeStart = $this->getMicrotime($date);
        $result = call_user_func_array($f, $args);
        $duration = (int) ($this->getMicrotime() - $timeStart);

        foreach ($this->handlers as $handler) {
            $handler->store($date, $hostname, $section, $duration);
        }

        return $result;
    }

    // @codeCoverageIgnoreStart
    protected function getHostname(): string
    {
        $hostname = gethostname();

        Assert::notFalse($hostname, 'Failed to get hostname');

        return $hostname;
    }

    protected function getMicrotime(CarbonImmutable $carbon = new CarbonImmutable()): float
    {
        return (float) $carbon->rawFormat('U.u') * 1000;
    }
    // @codeCoverageIgnoreEnd
}
