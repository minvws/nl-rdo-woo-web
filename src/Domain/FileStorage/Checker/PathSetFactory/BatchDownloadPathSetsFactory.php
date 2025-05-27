<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker\PathSetFactory;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Domain\Publication\BatchDownload\BatchDownloadRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class BatchDownloadPathSetsFactory implements PathSetsFactoryInterface
{
    public function __construct(
        private BatchDownloadRepository $batchDownloadRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return \Generator<PathSet>
     */
    public function getPathSets(): \Generator
    {
        $filePaths = [];
        foreach ($this->batchDownloadRepository->findAll() as $entity) {
            $filePaths['/' . $entity->getFilename()] = $entity->getId()->toRfc4122();
            $this->entityManager->detach($entity);
        }

        yield new PathSet('BatchDownload', FileStorageType::BATCH, $filePaths);
    }
}
