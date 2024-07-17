<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\AntiVirus;

use App\Domain\Upload\AntiVirus\ClamAvUploadValidator;
use App\Entity\User;
use App\Service\Storage\LocalFilesystem;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

final class ClamAvUploadValidatorTest extends MockeryTestCase
{
    private Client&MockInterface $clamAvClient;
    private LoggerInterface&MockInterface $logger;
    private LocalFilesystem&MockInterface $filesystem;
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private AuditLogger $auditLogger;
    private User&MockInterface $user;
    private Security&MockInterface $security;
    private ClamAvUploadValidator $validator;

    public function setUp(): void
    {
        $this->clamAvClient = \Mockery::mock(Client::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->filesystem = \Mockery::mock(LocalFilesystem::class);

        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->user = \Mockery::mock(User::class);
        $this->user->shouldReceive('getAuditId')->andReturn('user-id');

        $this->security = \Mockery::mock(Security::class);
        $this->security->shouldReceive('getUser')->andReturn($this->user);

        $this->validator = new ClamAvUploadValidator(
            $this->clamAvClient,
            $this->logger,
            $this->filesystem,
            $this->auditLogger,
            $this->security,
        );

        parent::setUp();
    }

    public function testValidateThrowsErrorWhenStreamCannotBeOpened(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn('/foo/bar/non.existent');

        $this->filesystem->expects('createStream')->with('/foo/bar/non.existent', 'r')->andReturnFalse();

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_TECHNICAL);

        $this->validator->onValidate($event);
    }

    public function testValidateThrowsErrorWhenClamAvResponseIsNotOk(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn('/some/path/foo.txt');
        $event->shouldReceive('getFile->getBasename')->andReturn('foo.txt');

        $stream = fopen('php://memory', 'r');
        $this->filesystem->expects('createStream')->with('/some/path/foo.txt', 'r')->andReturn($stream);

        $result = \Mockery::mock(Result::class);
        $result->expects('hasFailed')->twice()->andReturnTrue();
        $result->expects('getReason')->twice()->andReturns('foo bar');

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->logger->expects('error');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (FileUploadLogEvent $event): bool {
                self::assertTrue($event->getLogData()['failed']);
                self::assertEquals('foo bar', $event->getLogData()['failed_reason']);

                return true;
            }
        ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_UNSAFE);

        $this->validator->onValidate($event);
    }

    public function testValidateThrowsErrorWhenClamAvCheckFails(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn('/some/path/foo.txt');

        $stream = fopen('php://memory', 'r');
        $this->filesystem->expects('createStream')->with('/some/path/foo.txt', 'r')->andReturn($stream);

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andThrow(new \RuntimeException('oops'));

        $this->logger->expects('error');
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(ClamAvUploadValidator::ERROR_TECHNICAL);

        $this->validator->onValidate($event);
    }

    public function testValidateContinuesWithoutExceptionWhenClamAvResponseIsOk(): void
    {
        $event = \Mockery::mock(ValidationEvent::class);
        $event->shouldReceive('getFile->getPathname')->andReturn('/some/path/foo.txt');

        $stream = fopen('php://memory', 'r');
        $this->filesystem->expects('createStream')->with('/some/path/foo.txt', 'r')->andReturn($stream);

        $result = \Mockery::mock(Result::class);
        $result->expects('hasFailed')->twice()->andReturnFalse();
        $result->expects('getReason')->andReturnNull();

        $this->clamAvClient->expects('scanResourceStream')->with($stream)->andReturn($result);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (FileUploadLogEvent $event): bool {
                self::assertFalse($event->getLogData()['failed']);
                self::assertEquals('', $event->getLogData()['failed_reason']);

                return true;
            }
        ));

        $this->validator->onValidate($event);
    }
}
