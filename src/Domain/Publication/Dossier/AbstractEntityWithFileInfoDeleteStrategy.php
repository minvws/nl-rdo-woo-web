<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Entity\EntityWithFileInfo;
use App\Service\Storage\DocumentStorageService;

abstract readonly class AbstractEntityWithFileInfoDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private DocumentStorageService $storageService,
    ) {
    }

    protected function deleteFileForEntity(?EntityWithFileInfo $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->storageService->removeFileForEntity($entity);
    }

    abstract public function delete(AbstractDossier $dossier): void;
}
