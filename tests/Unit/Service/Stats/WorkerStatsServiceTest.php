<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Stats;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\Stats\Handler\StatsHandlerInterface;
use Shared\Service\Stats\WorkerStatsService;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function func_get_args;

final class WorkerStatsServiceTest extends UnitTestCase
{
    public function testMeasure(): void
    {
        $testNow = CarbonImmutable::make('2010-01-01 00:00 UTC');
        Assert::notNull($testNow);
        CarbonImmutable::setTestNow($testNow);

        $args = ['a', 'b'];

        $durationSec = 35;
        $duration = $durationSec * 1000;

        $closureResult = 'test-result';
        $closure = function () use ($closureResult, $testNow, $durationSec, $args): string {
            CarbonImmutable::setTestNow($testNow->addSeconds($durationSec));

            $this->assertSame($args, func_get_args());

            return $closureResult;
        };

        $hostname = 'test-hostname';
        $section = 'test-section';
        $statsHandlerOne = Mockery::mock(StatsHandlerInterface::class);
        $statsHandlerOne
            ->shouldReceive('store')
            ->once()
            ->with(
                Mockery::on(fn (DateTimeImmutable $date): bool => $date == $testNow),
                $hostname,
                $section,
                $duration,
            );
        $statsHandlerTwo = Mockery::mock(StatsHandlerInterface::class);
        $statsHandlerTwo
            ->shouldReceive('store')
            ->once()
            ->with(
                Mockery::on(fn (DateTimeImmutable $date): bool => $date == $testNow),
                $hostname,
                $section,
                $duration,
            );

        $handlers = [$statsHandlerOne, $statsHandlerTwo];

        /** @var WorkerStatsService&MockInterface $statsService */
        $statsService = Mockery::mock(WorkerStatsService::class, [$handlers])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $statsService
            ->shouldReceive('getHostname')
            ->once()
            ->andReturn($hostname);
        $result = $statsService->measure($section, $closure, $args);

        $this->assertSame($closureResult, $result);
    }
}
