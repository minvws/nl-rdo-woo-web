<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\IngestDossiersMessage;
use App\Repository\DossierRepository;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class IngestDossiersHandler
{
    public function __construct(
        protected DossierRepository $dossierRepository,
        protected IngestService $ingester,
        protected MessageBusInterface $bus,
        protected ElasticService $elasticService,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(IngestDossiersMessage $message): void
    {
        try {
            $options = new Options();

            $dossiers = $this->dossierRepository->findAllPublishable();
            foreach ($dossiers as $dossier) {
                $this->elasticService->updateDossier($dossier, false);

                foreach ($dossier->getDocuments() as $document) {
                    $this->ingester->ingest($document, $options);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error when ingesting all dossiers', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
