<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;

readonly class LazyFileReference implements FileReferenceInterface
{
    public function __construct(
        private EntityWithFileInfo $entity,
        private ContentExtractOptions $contentExtractOptions,
        private EntityStorageService $entityStorageService,
    ) {
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        if ($this->contentExtractOptions->hasPageNumber()) {
            throw ContentExtractException::forCannotCreateLazyFileReferenceForPage();
        }

        $stream = $this->entityStorageService->retrieveResourceEntity($this->entity);
        if ($stream === null) {
            throw ContentExtractException::forCannotCreateLazyFileReference($this->entity);
        }

        return $stream;
    }
}
