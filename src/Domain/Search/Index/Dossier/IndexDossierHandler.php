<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * This handler (re)indexes dossier data into elasticsearch.
 */
#[AsMessageHandler]
class IndexDossierHandler
{
    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly DossierIndexer $dossierIndexer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(IndexDossierCommand $message): void
    {
        try {
            $dossier = $this->dossierRepository->find($message->getUuid());
            if ($dossier === null) {
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $this->dossierIndexer->index($dossier, $message->getRefresh());
        } catch (Exception $e) {
            $this->logger->error('Failed to update dossier in elasticsearch', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
