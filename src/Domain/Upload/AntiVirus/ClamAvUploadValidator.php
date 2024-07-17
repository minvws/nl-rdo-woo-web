<?php

declare(strict_types=1);

namespace App\Domain\Upload\AntiVirus;

use App\Entity\User;
use App\Service\Storage\LocalFilesystem;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use Oneup\UploaderBundle\Event\ValidationEvent;
use Oneup\UploaderBundle\Uploader\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

// Intentionally not enabled for the 'test' environment
#[When(env: 'dev')]
#[When(env: 'prod')]
#[AsEventListener(event: 'oneup_uploader.validation', method: 'onValidate')]
readonly class ClamAvUploadValidator
{
    public const ERROR_TECHNICAL = 'error.technical';
    public const ERROR_UNSAFE = 'error.unsafe';

    public function __construct(
        private Client $clamAvClient,
        private LoggerInterface $logger,
        private LocalFilesystem $filesystem,
        private AuditLogger $auditLogger,
        private Security $security,
    ) {
    }

    public function onValidate(ValidationEvent $event): void
    {
        $handle = $this->filesystem->createStream($event->getFile()->getPathname(), 'r');
        if (! is_resource($handle)) {
            $this->logger->error('Could not open stream for upload antivirus validation');
            throw new ValidationException(self::ERROR_TECHNICAL);
        }

        try {
            $result = $this->clamAvClient->scanResourceStream($handle);
        } catch (\Throwable $throwable) {
            $this->logger->error('An error occurred during upload antivirus validation: ' . $throwable->getMessage());
            throw new ValidationException(self::ERROR_TECHNICAL);
        }

        $this->logToAuditLog($result, $event);

        if ($result->hasFailed()) {
            $this->logger->error(sprintf(
                'Upload antivirus validation for file "%s" failed with reason: %s',
                $event->getFile()->getBasename(),
                $result->getReason(),
            ));

            throw new ValidationException(self::ERROR_UNSAFE);
        }
    }

    private function logToAuditLog(Result $result, ValidationEvent $event): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $logEvent = (new FileUploadLogEvent())
            ->withActor($user)
            ->withData([
                'filename' => $event->getFile()->getPathname(),
            ])
            ->withFailed($result->hasFailed(), $result->getReason() ?? '');

        $this->auditLogger->log($logEvent);
    }
}
