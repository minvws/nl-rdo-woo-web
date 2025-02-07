<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Publication\BatchDownload;
use App\Message\GenerateArchiveMessage;
use App\Service\ArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * This handler will process a generate archive message. Its task is to generate a ZIP archive file for the given documents in the message.
 */
#[AsMessageHandler]
class GenerateArchiveHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected ArchiveService $archiveService;

    public function __construct(
        EntityManagerInterface $doctrine,
        LoggerInterface $logger,
        ArchiveService $archiveService,
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->archiveService = $archiveService;
    }

    public function __invoke(GenerateArchiveMessage $message): void
    {
        $batch = $this->doctrine->getRepository(BatchDownload::class)->find($message->getUuid());
        if (! $batch) {
            $this->logger->error('Cannot find batch download entity', [
                'id' => $message->getUuid(),
            ]);

            return;
        }

        if (! $this->archiveService->generateArchive($batch)) {
            $this->logger->error('Failed to generate ZIP archive file', [
                'id' => $message->getUuid()->toRfc4122(),
            ]);
        }
    }
}
