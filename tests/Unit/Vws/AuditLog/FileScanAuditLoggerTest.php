<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Vws\AuditLog;

use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\MockInterface;
use Shared\Domain\Upload\AntiVirus\FileScannedEvent;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Vws\AuditLog\FileScanAuditLogger;
use Symfony\Bundle\SecurityBundle\Security;

final class FileScanAuditLoggerTest extends UnitTestCase
{
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private Security&MockInterface $security;
    private AuditLogger $auditLogger;
    private FileScanAuditLogger $fileScanLogger;

    protected function setUp(): void
    {
        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();

        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->security = \Mockery::mock(Security::class);

        $this->fileScanLogger = new FileScanAuditLogger(
            $this->auditLogger,
            $this->security,
        );

        parent::setUp();
    }

    public function testOnFileScanned(): void
    {
        $path = '/foo/bar/test.txt';
        $reason = 'test reason';

        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('user-id');
        $this->security->shouldReceive('getUser')->andReturn($user);

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            static function (FileUploadLogEvent $event) use ($reason): bool {
                self::assertTrue($event->getLogData()['failed']);
                self::assertEquals($reason, $event->getLogData()['failed_reason']);

                return true;
            }
        ));

        $this->fileScanLogger->onFileScanned(new FileScannedEvent(
            path: $path,
            hasFailed: true,
            reason: $reason,
        ));
    }
}
