<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\RemoveDocumentMessage;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveDocumentHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $doctrine,
        private readonly DocumentService $documentService,
    ) {
    }

    public function __invoke(RemoveDocumentMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getDossierId());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getDossierId(),
            ]);

            return;
        }

        $document = $this->doctrine->getRepository(Document::class)->find($message->getDocumentId());
        if (! $document) {
            $this->logger->warning('No document found for this message', [
                'uuid' => $message->getDocumentId(),
            ]);

            return;
        }

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }
}
