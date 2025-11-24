<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

use Shared\Domain\Publication\BatchDownload\Type\BatchDownloadTypeInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class BatchDownloadService
{
    /**
     * @param iterable<BatchDownloadTypeInterface> $types
     */
    public function __construct(
        private BatchDownloadRepository $batchRepository,
        private BatchDownloadDispatcher $dispatcher,
        private BatchDownloadStorage $storage,
        #[AutowireIterator('domain.public.batchdownload.type')]
        private iterable $types,
    ) {
    }

    public function refresh(BatchDownloadScope $scope): void
    {
        $batches = $this->batchRepository->getAllForScope($scope);
        foreach ($batches as $batch) {
            if ($batch->getStatus()->isOutdated()) {
                continue;
            }

            $batch->markAsOutdated();
            $this->batchRepository->save($batch);
        }

        $type = $this->getType($scope);
        if (! $type->isAvailableForBatchDownload($scope)) {
            return;
        }

        if ($scope->containsBothInquiryAndWooDecision()) {
            return;
        }

        $this->create($scope);
    }

    public function find(BatchDownloadScope $scope): ?BatchDownload
    {
        return $this->batchRepository->getBestAvailableBatchDownloadForScope($scope);
    }

    public function findOrCreate(BatchDownloadScope $scope): BatchDownload
    {
        return $this->find($scope) ?? $this->create($scope);
    }

    public function create(BatchDownloadScope $scope): BatchDownload
    {
        if ($scope->containsBothInquiryAndWooDecision()) {
            return $this->findOrCreate(BatchDownloadScope::forWooDecision($scope->wooDecision));
        }

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

    public function exists(BatchDownload $batch): bool
    {
        return $this->batchRepository->exists($batch->getId());
    }
}
