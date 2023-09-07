<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateOfficialMessage;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update an official in elasticsearch.
 */
#[AsMessageHandler]
class UpdateOfficialHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected ElasticService $elasticService;

    public function __construct(
        ElasticService $elasticService,
        LoggerInterface $logger
    ) {
        $this->elasticService = $elasticService;
        $this->logger = $logger;
    }

    public function __invoke(UpdateOfficialMessage $message): void
    {
        try {
            $this->elasticService->updateOfficial($message->getOld(), $message->getNew());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update official in elasticsearch', [
                'id' => $message->getOld()->getId(),
                'old' => $message->getOld()->getName(),
                'new' => $message->getNew()->getName(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
