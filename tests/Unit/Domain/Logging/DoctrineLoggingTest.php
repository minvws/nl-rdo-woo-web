<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Logging;

use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Error;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Logging\DoctrineLogging;
use Shared\Domain\Logging\LoggingTypeInterface;
use Shared\Tests\Unit\UnitTestCase;

final class DoctrineLoggingTest extends UnitTestCase
{
    private Configuration&MockInterface $configuration;
    private DoctrineLogging $doctrineLogging;

    protected function setUp(): void
    {
        $this->configuration = Mockery::mock(Configuration::class);

        $connection = Mockery::mock(Connection::class);
        $connection->allows('getConfiguration')->andReturn($this->configuration);

        $doctrine = Mockery::mock(EntityManagerInterface::class);
        $doctrine->allows('getConnection')->andReturn($connection);

        $this->doctrineLogging = new DoctrineLogging($doctrine);

        parent::setUp();
    }

    public function testItIsALoggingType(): void
    {
        self::assertInstanceOf(LoggingTypeInterface::class, $this->doctrineLogging);
    }

    public function testItIsNotDisabledInitially(): void
    {
        self::assertFalse($this->doctrineLogging->isDisabled());
    }

    public function testDisableRemovesTheDebugAndLoggingMiddlewares(): void
    {
        $keptMiddleware = Mockery::mock(Middleware::class);

        $this->configuration->expects('getMiddlewares')->andReturn([
            Mockery::mock(DebugMiddleware::class),
            $keptMiddleware,
            Mockery::mock(LoggingMiddleware::class),
        ]);
        $this->configuration->expects('setMiddlewares')->with([$keptMiddleware]);

        $this->doctrineLogging->disable();

        self::assertTrue($this->doctrineLogging->isDisabled());
    }

    public function testDisableKeepsAllMiddlewaresWhenNoneAreDisallowed(): void
    {
        $middlewares = [Mockery::mock(Middleware::class), Mockery::mock(Middleware::class)];

        $this->configuration->expects('getMiddlewares')->andReturn($middlewares);
        $this->configuration->expects('setMiddlewares')->with($middlewares);

        $this->doctrineLogging->disable();

        self::assertTrue($this->doctrineLogging->isDisabled());
    }

    public function testDisableWithoutAnyMiddlewares(): void
    {
        $this->configuration->expects('getMiddlewares')->andReturn([]);
        $this->configuration->expects('setMiddlewares')->with([]);

        $this->doctrineLogging->disable();

        self::assertTrue($this->doctrineLogging->isDisabled());
    }

    public function testRestorePutsTheOriginalMiddlewaresBack(): void
    {
        $debugMiddleware = Mockery::mock(DebugMiddleware::class);
        $keptMiddleware = Mockery::mock(Middleware::class);
        $originalMiddlewares = [$debugMiddleware, $keptMiddleware];

        $this->configuration->expects('getMiddlewares')->andReturn($originalMiddlewares);
        $this->configuration->expects('setMiddlewares')->with([$keptMiddleware]);

        $this->doctrineLogging->disable();

        $this->configuration->expects('setMiddlewares')->with($originalMiddlewares);

        $this->doctrineLogging->restore();
    }

    public function testRestoreWithoutDisableSetsAnEmptyMiddlewareList(): void
    {
        $this->configuration->expects('setMiddlewares')->with([]);

        $this->doctrineLogging->restore();
    }

    public function testIsDisabledCannotBeCalledAfterRestore(): void
    {
        $this->configuration->expects('getMiddlewares')->andReturn([]);
        $this->configuration->expects('setMiddlewares')->with([])->twice();

        $this->doctrineLogging->disable();
        $this->doctrineLogging->restore();

        $this->expectException(Error::class);

        $this->doctrineLogging->isDisabled();
    }
}
