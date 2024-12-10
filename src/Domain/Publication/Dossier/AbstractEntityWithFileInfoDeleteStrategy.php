<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Storage\EntityStorageService;

abstract readonly class AbstractEntityWithFileInfoDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private EntityStorageService $entityStorageService,
    ) {
    }

    protected function deleteFileForEntity(?EntityWithFileInfo $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->entityStorageService->removeFileForEntity($entity);
    }

    abstract public function delete(AbstractDossier $dossier): void;
}
