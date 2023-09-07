<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\UpdateDossierMessage;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update a dossier data based on info in the database into elasticsearch.
 */
#[AsMessageHandler]
class UpdateDossierHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected ElasticService $elasticService;

    public function __construct(
        EntityManagerInterface $doctrine,
        ElasticService $elasticService,
        LoggerInterface $logger
    ) {
        $this->elasticService = $elasticService;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
    }

    public function __invoke(UpdateDossierMessage $message): void
    {
        try {
            $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
            if (! $dossier) {
                // No dossier found for this message
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }
            $this->elasticService->updateDossier($dossier);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update dossier in elasticsearch', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
