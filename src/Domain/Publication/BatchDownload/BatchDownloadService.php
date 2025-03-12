<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload;

use App\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class BatchDownloadService
{
    /**
     * @var iterable<BatchDownloadTypeInterface>
     */
    private iterable $types;

    /**
     * @param iterable<BatchDownloadTypeInterface> $types
     */
    public function __construct(
        private BatchDownloadRepository $batchRepository,
        private BatchDownloadDispatcher $dispatcher,
        private BatchDownloadStorage $storage,
        #[AutowireIterator('domain.public.batchdownload.type')]
        iterable $types,
    ) {
        $this->types = $types;
    }

    public function refresh(BatchDownloadScope $scope): void
    {
        $batches = $this->batchRepository->getAllForScope($scope);
        foreach ($batches as $batch) {
            $batch->markAsOutdated();
            $this->batchRepository->save($batch);
        }

        $type = $this->getType($scope);
        if (! $type->isAvailableForBatchDownload($scope)) {
            return;
        }

        $this->create($scope);
    }

    public function findOrCreate(BatchDownloadScope $scope): BatchDownload
    {
        // If a batch already exists, re-use this and return early.
        $batch = $this->batchRepository->getBestAvailableBatchDownloadForScope($scope);
        if ($batch) {
            return $batch;
        }

        return $this->create($scope);
    }

    public function create(BatchDownloadScope $scope): BatchDownload
    {
        $batch = new BatchDownload(
            $scope,
            new \DateTimeImmutable('+10 years'),
        );

        $this->batchRepository->save($batch);

        $this->dispatcher->dispatchGenerateBatchDownloadCommand($batch);

        return $batch;
    }

    public function removeAllForScope(BatchDownloadScope $scope): void
    {
        $batches = $this->batchRepository->getAllForScope($scope);
        foreach ($batches as $batch) {
            $this->storage->removeFileForBatch($batch);
            $this->batchRepository->remove($batch);
        }
    }

    public function getType(BatchDownloadScope $scope): BatchDownloadTypeInterface
    {
        foreach ($this->types as $type) {
            if ($type->supports($scope)) {
                return $type;
            }
        }

        throw new \OutOfBoundsException('Scope not supported');
    }
}
