<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Stats;

use App\Service\Stats\Handler\StatsHandlerInterface;
use App\Service\Stats\WorkerStatsService;
use App\Tests\Unit\UnitTestCase;
use Carbon\CarbonImmutable;
use phpmock\mockery\PHPMockery;

final class WorkerStatsServiceTest extends UnitTestCase
{
    private const TEST_NAMESPACE = 'App\\Service\\Stats';

    protected function setUp(): void
    {
        parent::setUp();

        // If the actual function is called before the mock was defined, the test can fail:
        PHPMockery::define(self::TEST_NAMESPACE, 'gethostname');
        PHPMockery::define(self::TEST_NAMESPACE, 'microtime');
        PHPMockery::define(self::TEST_NAMESPACE, 'call_user_func_array');
    }

    public function testMeasure(): void
    {
        $testNow = CarbonImmutable::make('2010-01-01 00:00 UTC');
        CarbonImmutable::setTestNow($testNow);

        $closureResult = 'test-result';
        $closure = fn (): string => $closureResult;
        $args = ['a', 'b'];

        PHPMockery::mock(self::TEST_NAMESPACE, 'gethostname')
            ->once()
            ->andReturn($hostname = 'test-hostname');
        $microtime = PHPMockery::mock(self::TEST_NAMESPACE, 'microtime');
        $microtime
            ->once()
            ->with(true)
            ->andReturn(1000.0);
        $microtime
            ->once()
            ->with(true)
            ->andReturn(2500.0);
        PHPMockery::mock(self::TEST_NAMESPACE, 'call_user_func_array')
            ->once()
            ->with($closure, $args)
            ->andReturn($closureResult);

        $duration = 1500000;
        $section = 'test-section';

        $statsHandlerOne = \Mockery::mock(StatsHandlerInterface::class);
        $statsHandlerOne
            ->shouldReceive('store')
            ->once()
            ->with(
                \Mockery::on(fn (\DateTimeImmutable $date): bool => $date == $testNow),
                $hostname,
                $section,
                $duration,
            );
        $statsHandlerTwo = \Mockery::mock(StatsHandlerInterface::class);
        $statsHandlerTwo
            ->shouldReceive('store')
            ->once()
            ->with(
                \Mockery::on(fn (\DateTimeImmutable $date): bool => $date == $testNow),
                $hostname,
                $section,
                $duration,
            );

        $statsService = new WorkerStatsService([$statsHandlerOne, $statsHandlerTwo]);
        $result = $statsService->measure($section, $closure, $args);

        $this->assertSame($closureResult, $result);
    }
}
