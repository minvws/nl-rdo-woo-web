<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\IngestDossiersMessage;
use App\Repository\AbstractDossierRepository;
use App\Service\DossierService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class IngestDossiersHandler
{
    public function __construct(
        readonly private AbstractDossierRepository $dossierRepository,
        readonly private LoggerInterface $logger,
        readonly private DossierService $dossierService,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(IngestDossiersMessage $message): void
    {
        try {
            // Important: use an iterator instead of fetching all dossiers into memory at once
            $query = $this->dossierRepository->createQueryBuilder('d')->getQuery();
            foreach ($query->toIterable() as $dossier) {
                // This call will dispatch a message per dossier => per dossier doc => per doc page (fan-out)
                $this->dossierService->ingest($dossier);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error while triggering ingest for all dossiers', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
