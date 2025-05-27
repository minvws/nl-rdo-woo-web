<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\AntiVirus;

use App\Domain\Upload\AntiVirus\ClamAvClientFactory;
use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\AntiVirus\FileScanResult;
use App\Entity\User;
use App\Service\Storage\LocalFilesystem;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

final class ClamAvFileScannerTest extends MockeryTestCase
{
    private Client&MockInterface $clamAvClient;
    private ClamAvClientFactory&MockInterface $clientFactory;
    private LoggerInterface&MockInterface $logger;
    private LocalFilesystem&MockInterface $filesystem;
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private AuditLogger $auditLogger;
    private User&MockInterface $user;
    private Security&MockInterface $security;
    private ClamAvFileScanner $scanner;

    public function setUp(): void
    {
        $this->clamAvClient = \Mockery::mock(Client::class);
        $this->clientFactory = \Mockery::mock(ClamAvClientFactory::class);
        $this->clientFactory->shouldReceive('getClient')->andReturn($this->clamAvClient);

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->filesystem = \Mockery::mock(LocalFilesystem::class);

        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->user = \Mockery::mock(User::class);
        $this->user->shouldReceive('getAuditId')->andReturn('user-id');

        $this->security = \Mockery::mock(Security::class);
        $this->security->shouldReceive('getUser')->andReturn($this->user);

        $this->scanner = new ClamAvFileScanner(
            $this->clientFactory,
            $this->logger,
            $this->filesystem,
            $this->auditLogger,
            $this->security,
            10,
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

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andThrow(new \RuntimeException('oops'));

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

        $result = \Mockery::mock(Result::class);
        $result->shouldReceive('hasFailed')->andReturnTrue();
        $result->shouldReceive('getReason')->andReturns($reason = 'foo bar');

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->logger->expects('error');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (FileUploadLogEvent $event) use ($reason): bool {
                self::assertTrue($event->getLogData()['failed']);
                self::assertEquals($reason, $event->getLogData()['failed_reason']);

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

        $result = \Mockery::mock(Result::class);
        $result->shouldReceive('hasFailed')->andReturnFalse();
        $result->shouldReceive('getReason')->andReturnNull();

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (FileUploadLogEvent $event): bool {
                self::assertFalse($event->getLogData()['failed']);
                self::assertEquals('', $event->getLogData()['failed_reason']);

                return true;
            }
        ));

        self::assertEquals(
            FileScanResult::SAFE,
            $this->scanner->scan($path),
        );
    }
}
