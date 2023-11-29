<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateDepartmentMessage;
use App\Repository\DepartmentRepository;
use App\Service\Elastic\ElasticService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update a department in elasticsearch.
 */
#[AsMessageHandler]
class UpdateDepartmentHandler
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly ElasticService $elasticService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateDepartmentMessage $message): void
    {
        $department = $this->repository->find($message->getUuid());
        if (! $department) {
            throw new \RuntimeException('Cannot find department with UUID ' . $message->getUuid());
        }

        try {
            $this->elasticService->updateDepartment($department);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update department in elasticsearch', [
                'id' => $message->getUuid()->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
