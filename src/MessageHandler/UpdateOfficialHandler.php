<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateOfficialMessage;
use App\Repository\GovernmentOfficialRepository;
use App\Service\Elastic\ElasticService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update an official in elasticsearch.
 */
#[AsMessageHandler]
class UpdateOfficialHandler
{
    public function __construct(
        private readonly ElasticService $elasticService,
        private readonly LoggerInterface $logger,
        private readonly GovernmentOfficialRepository $repository,
    ) {
    }

    public function __invoke(UpdateOfficialMessage $message): void
    {
        $official = $this->repository->find($message->getUuid());
        if (! $official) {
            throw new \RuntimeException('Cannot find government official with UUID ' . $message->getUuid());
        }

        try {
            $this->elasticService->updateOfficial($official);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update official in elasticsearch', [
                'id' => $message->getUuid()->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
