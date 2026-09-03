<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Logging;

use Error;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shared\DataCollector\ElasticCollector;
use Shared\Domain\Logging\ElasticSearchLogging;
use Shared\Domain\Logging\LoggingTypeInterface;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;

final class ElasticSearchLoggingTest extends UnitTestCase
{
    private ElasticService&MockInterface $elasticService;
    private ElasticCollector&MockInterface $elasticCollector;
    private ElasticSearchLogging $elasticSearchLogging;

    protected function setUp(): void
    {
        $this->elasticService = Mockery::mock(ElasticService::class);
        $this->elasticCollector = Mockery::mock(ElasticCollector::class);

        $this->elasticSearchLogging = new ElasticSearchLogging(
            $this->elasticService,
            $this->elasticCollector,
        );

        parent::setUp();
    }

    public function testItIsALoggingType(): void
    {
        self::assertInstanceOf(LoggingTypeInterface::class, $this->elasticSearchLogging);
    }

    public function testItIsNotDisabledInitially(): void
    {
        self::assertFalse($this->elasticSearchLogging->isDisabled());
    }

    public function testDisableSwapsInANullLoggerAndDisablesTheCollector(): void
    {
        $this->elasticService->expects('getLogger')->andReturn(Mockery::mock(LoggerInterface::class));
        $this->elasticService->expects('setLogger')->with(Mockery::type(NullLogger::class));
        $this->elasticCollector->expects('disable');

        $this->elasticSearchLogging->disable();

        self::assertTrue($this->elasticSearchLogging->isDisabled());
    }

    public function testRestorePutsTheOriginalLoggerBackAndEnablesTheCollector(): void
    {
        $originalLogger = Mockery::mock(LoggerInterface::class);

        $this->elasticService->expects('getLogger')->andReturn($originalLogger);
        $this->elasticService->expects('setLogger')->with(Mockery::type(NullLogger::class));
        $this->elasticCollector->expects('disable');

        $this->elasticSearchLogging->disable();

        $this->elasticService->expects('setLogger')->with($originalLogger);
        $this->elasticCollector->expects('enable');

        $this->elasticSearchLogging->restore();
    }

    public function testRestoreWithoutDisableCannotAccessTheOriginalLogger(): void
    {
        $this->expectException(Error::class);

        $this->elasticSearchLogging->restore();
    }

    public function testItStaysDisabledAfterRestore(): void
    {
        $this->elasticService->expects('getLogger')->andReturn(Mockery::mock(LoggerInterface::class));
        $this->elasticService->expects('setLogger')->twice();
        $this->elasticCollector->expects('disable');
        $this->elasticCollector->expects('enable');

        $this->elasticSearchLogging->disable();
        $this->elasticSearchLogging->restore();

        self::assertTrue($this->elasticSearchLogging->isDisabled());
    }
}
