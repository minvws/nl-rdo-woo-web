<?php

declare(strict_types=1);

namespace App\Domain\Upload\AntiVirus;

use App\Entity\User;
use App\Service\Storage\LocalFilesystem;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\FileUploadLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Xenolope\Quahog\Result;

readonly class ClamAvFileScanner
{
    public function __construct(
        private ClamAvClientFactory $clientFactory,
        private LoggerInterface $logger,
        private LocalFilesystem $filesystem,
        private AuditLogger $auditLogger,
        private Security $security,
        private int $fileSizeLimit,
    ) {
    }

    public function getFileSizeLimit(): int
    {
        return $this->fileSizeLimit;
    }

    public function scan(string $path): FileScanResult
    {
        $stream = $this->filesystem->createStream($path, 'r');
        if (! $stream) {
            $this->logger->error('Could not open stream for antivirus validation');

            return FileScanResult::TECHNICAL_ERROR;
        }

        return $this->scanResource($path, $stream);
    }

    /**
     * @param resource $handle
     */
    public function scanResource(string $path, $handle): FileScanResult
    {
        if (! is_resource($handle)) {
            $this->logger->error('Invalid stream provided for antivirus validation');

            return FileScanResult::TECHNICAL_ERROR;
        }

        $fileStats = fstat($handle);
        if ($fileStats === false || ! isset($fileStats['size']) || $fileStats['size'] < 1) {
            $this->logger->error('Could not determine stream size for antivirus validation');

            return FileScanResult::TECHNICAL_ERROR;
        }

        if ($fileStats['size'] > $this->fileSizeLimit) {
            $this->logger->warning(sprintf(
                'Max file size exceeded for antivirus validation. Filesize: %d, scan limit: %d (bytes)',
                $fileStats['size'],
                $this->fileSizeLimit,
            ));

            return FileScanResult::MAX_SIZE_EXCEEDED;
        }

        try {
            $client = $this->clientFactory->getClient();
            $result = $client->scanResourceStream($handle);
        } catch (\Throwable $throwable) {
            $this->logger->error('An error occurred during antivirus validation: ' . $throwable->getMessage());

            return FileScanResult::TECHNICAL_ERROR;
        }

        $this->logToAuditLog($result, $path);

        if ($result->hasFailed()) {
            $this->logger->error(sprintf(
                'Antivirus validation for file "%s" failed with reason: %s',
                $path,
                $result->getReason(),
            ));

            return FileScanResult::UNSAFE;
        }

        return FileScanResult::SAFE;
    }

    private function logToAuditLog(Result $result, string $path): void
    {
        $logEvent = (new FileUploadLogEvent())
            ->withData([
                'filename' => $path,
            ])
            ->withFailed($result->hasFailed(), $result->getReason() ?? '');

        /** @var ?User $user */
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $logEvent->withActor($user);
        }

        $this->auditLogger->log($logEvent);
    }
}
