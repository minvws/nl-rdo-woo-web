<?php

declare(strict_types=1);

namespace Shared\Domain\FileStorage\Checker\PathSetFactory;

use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Shared\Domain\FileStorage\Checker\FileStorageType;
use Shared\Domain\FileStorage\Checker\PathSet;
use Shared\Domain\Publication\BatchDownload\BatchDownloadRepository;

readonly class BatchDownloadPathSetsFactory implements PathSetsFactoryInterface
{
    public function __construct(
        private BatchDownloadRepository $batchDownloadRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Generator<PathSet>
     */
    public function getPathSets(): Generator
    {
        $filePaths = [];
        foreach ($this->batchDownloadRepository->findAll() as $entity) {
            $filePaths['/' . $entity->getFilename()] = $entity->getId()->toRfc4122();
            $this->entityManager->detach($entity);
        }

        yield new PathSet('BatchDownload', FileStorageType::BATCH, $filePaths);
    }
}
