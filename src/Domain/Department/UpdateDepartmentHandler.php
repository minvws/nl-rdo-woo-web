<?php

declare(strict_types=1);

namespace Shared\Domain\Department;

use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Search\Index\Updater\DepartmentIndexUpdater;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update a department in elasticsearch.
 */
#[AsMessageHandler]
class UpdateDepartmentHandler
{
    public function __construct(
        private readonly DepartmentRepository $departmentRepository,
        private readonly DepartmentIndexUpdater $departmentIndexUpdater,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateDepartmentCommand $message): void
    {
        $department = $this->departmentRepository->find($message->getUuid());
        if (! $department) {
            throw new RuntimeException('Cannot find department with UUID ' . $message->getUuid());
        }

        try {
            $this->departmentIndexUpdater->update($department);
        } catch (Exception $e) {
            $this->logger->error('Failed to update department in elasticsearch', [
                'id' => $message->getUuid()->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
