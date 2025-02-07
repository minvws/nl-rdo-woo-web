<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\SearchDispatcher;
use App\Service\Storage\EntityStorageService;

readonly class MainDocumentDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function __construct(
        private SearchDispatcher $dispatcher,
        EntityStorageService $entityStorageService,
    ) {
        parent::__construct($entityStorageService);
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithMainDocument || $dossier->getMainDocument() === null) {
            return;
        }

        $this->deleteFileForEntity($dossier->getMainDocument());

        $this->dispatcher->dispatchDeleteElasticDocumentCommand(
            ElasticDocumentId::forObject($dossier->getMainDocument()),
        );
    }
}
