<?php

declare(strict_types=1);

namespace App\Vws\AuditLog;

use App\Domain\Upload\AntiVirus\FileScannedEvent;
use App\Entity\User;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: FileScannedEvent::class, method: 'onFileScanned')]
readonly class FileScanAuditLogger
{
    public function __construct(
        private AuditLogger $auditLogger,
        private Security $security,
    ) {
    }

    public function onFileScanned(FileScannedEvent $event): void
    {
        $logEvent = (new FileUploadLogEvent())
            ->withData([
                'filename' => $event->path,
            ])
            ->withFailed($event->hasFailed, $event->reason ?? '');

        /** @var ?User $user */
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $logEvent->withActor($user);
        }

        $this->auditLogger->log($logEvent);
    }
}
