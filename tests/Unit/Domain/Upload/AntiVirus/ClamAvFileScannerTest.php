<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\AntiVirus;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Upload\AntiVirus\ClamAvClientFactory;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\AntiVirus\FileScannedEvent;
use Shared\Domain\Upload\AntiVirus\FileScanResult;
use Shared\Service\Storage\LocalFilesystem;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

use function fopen;
use function fwrite;

final class ClamAvFileScannerTest extends UnitTestCase
{
    private Client&MockInterface $clamAvClient;
    private LoggerInterface&MockInterface $logger;
    private LocalFilesystem&MockInterface $filesystem;
    private EventDispatcherInterface&MockInterface $eventDispatcher;
    private ClamAvFileScanner $scanner;
    private ClamAvClientFactory&MockInterface $clientFactory;

    protected function setUp(): void
    {
        $this->clamAvClient = Mockery::mock(Client::class);
        $this->clientFactory = Mockery::mock(ClamAvClientFactory::class);
        $this->clientFactory->shouldReceive('getClient')->andReturn($this->clamAvClient);

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->filesystem = Mockery::mock(LocalFilesystem::class);

        $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->scanner = new ClamAvFileScanner(
            $this->clientFactory,
            $this->logger,
            $this->filesystem,
            10,
            $this->eventDispatcher,
        );

        parent::setUp();
    }

    public function testGetFileSizeLimit(): void
    {
        self::assertEquals(10, $this->scanner->getFileSizeLimit());
    }

    public function testScanReturnsTechnicalErrorWhenStreamCannotBeOpened(): void
    {
        $path = '/foo/bar/non.existent';

        $this->filesystem->expects('createStream')->with($path, 'r')->andReturnFalse();

        $this->logger->expects('error');

        self::assertEquals(
            FileScanResult::TECHNICAL_ERROR,
            $this->scanner->scan($path),
        );
    }

    public function testScanReturnsTechnicalErrorWhenStreamIsEmpty(): void
    {
        $path = '/foo/bar/non.existent';

        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        $this->filesystem->expects('createStream')->with($path, 'r')->andReturn($stream);

        $this->logger->expects('error');

        self::assertEquals(
            FileScanResult::TECHNICAL_ERROR,
            $this->scanner->scan($path),
        );
    }

    public function testScanReturnsSizeExceededWhenStreamIsTooBig(): void
    {
        $path = '/foo/bar/non.existent';

        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, 'some data that is more than 10 bytes');
        $this->filesystem->expects('createStream')->with($path, 'r')->andReturn($stream);

        $this->logger->expects('warning');

        self::assertEquals(
            FileScanResult::MAX_SIZE_EXCEEDED,
            $this->scanner->scan($path),
        );
    }

    public function testScanReturnsTechnicalErrorWhenClamAvThrowsException(): void
    {
        $path = '/foo/bar/test.txt';

        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, 'some data');
        $this->filesystem->expects('createStream')->with($path, 'r')->andReturn($stream);

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andThrow(new RuntimeException('oops'));

        $this->logger->expects('error');

        self::assertEquals(
            FileScanResult::TECHNICAL_ERROR,
            $this->scanner->scan($path),
        );
    }

    public function testScanReturnsUnsafeWhenClamAvReturnsAFailure(): void
    {
        $path = '/foo/bar/test.txt';

        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, 'some data');
        $this->filesystem->expects('createStream')->with($path, 'r')->andReturn($stream);

        $result = Mockery::mock(Result::class);
        $result->shouldReceive('hasFailed')->andReturnTrue();
        $result->shouldReceive('getReason')->andReturns($reason = 'foo bar');

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->logger->expects('error');

        $this->eventDispatcher->expects('dispatch')->with(Mockery::on(
            static function (FileScannedEvent $event) use ($reason): bool {
                self::assertTrue($event->hasFailed);
                self::assertEquals($reason, $event->reason);

                return true;
            }
        ));

        self::assertEquals(
            FileScanResult::UNSAFE,
            $this->scanner->scan($path),
        );
    }

    public function testScanReturnsSafeWhenClamAvReturnsNoFailure(): void
    {
        $path = '/foo/bar/test.txt';

        $stream = fopen('php://memory', 'r+');
        self::assertNotFalse($stream);
        fwrite($stream, 'some data');
        $this->filesystem->expects('createStream')->with($path, 'r')->andReturn($stream);

        $result = Mockery::mock(Result::class);
        $result->shouldReceive('hasFailed')->andReturnFalse();
        $result->shouldReceive('getReason')->andReturnNull();

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->eventDispatcher->expects('dispatch')->with(Mockery::on(
            static function (FileScannedEvent $event): bool {
                self::assertFalse($event->hasFailed);
                self::assertEquals('', $event->reason);

                return true;
            }
        ));

        self::assertEquals(
            FileScanResult::SAFE,
            $this->scanner->scan($path),
        );
    }
}
