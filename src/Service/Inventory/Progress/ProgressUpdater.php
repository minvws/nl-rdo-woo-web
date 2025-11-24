<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Progress;

use Doctrine\ORM\EntityManagerInterface;

class ProgressUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * This uses the DBAL connection instead of ORM to prevent have to do frequent and expensive entitymanager flushes.
     */
    public function updateProgressForRun(RunProgress $progress): void
    {
        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update('production_report_process_run')
            ->set('progress', strval($progress->getPercentage()))
            ->where('id = :runId')
            ->setParameter('runId', $progress->getRun()->getId()->toRfc4122())
            ->executeQuery();
    }
}
