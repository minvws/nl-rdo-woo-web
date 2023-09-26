<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\UpdateDossierArchivesMessage;
use App\Service\ArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDossierArchivesHandler
{
    public function __construct(
        private readonly ArchiveService $archiveService,
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(UpdateDossierArchivesMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
        if (! $dossier) {
            // No dossier found for this message
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        $this->archiveService->deleteDossierArchives($dossier);

        if ($dossier->getStatus() !== Dossier::STATUS_PUBLISHED && $dossier->getStatus() !== Dossier::STATUS_PREVIEW) {
            return;
        }

        if ($dossier->getUploadStatus()->getActualUploadCount() === 0) {
            return;
        }

        $this->archiveService->createArchiveForCompleteDossier($dossier);
    }
}
