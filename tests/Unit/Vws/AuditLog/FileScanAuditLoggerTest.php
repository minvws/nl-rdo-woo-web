<?php

declare(strict_types=1);

namespace App\Tests\Unit\Vws\AuditLog;

use App\Domain\Upload\AntiVirus\FileScannedEvent;
use App\Entity\User;
use App\Vws\AuditLog\FileScanAuditLogger;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class FileScanAuditLoggerTest extends MockeryTestCase
{
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private Security&MockInterface $security;
    private AuditLogger $auditLogger;
    private FileScanAuditLogger $fileScanLogger;

    public function setUp(): void
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
