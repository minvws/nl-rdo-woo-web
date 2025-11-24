<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;

readonly class MainDocumentDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function __construct(
        private SearchDispatcher $dispatcher,
        private ThumbnailStorageService $thumbnailStorage,
        EntityStorageService $entityStorageService,
    ) {
        parent::__construct($entityStorageService);
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithMainDocument || $dossier->getMainDocument() === null) {
            return;
        }

        $this->deleteAllFilesForEntity($dossier->getMainDocument());

        $this->thumbnailStorage->deleteAllThumbsForEntity($dossier->getMainDocument());

        $this->dispatcher->dispatchDeleteElasticDocumentCommand(
            ElasticDocumentId::forObject($dossier->getMainDocument()),
        );
    }
}
