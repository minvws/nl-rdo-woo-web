<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;

abstract readonly class AbstractEntityWithFileInfoDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private EntityStorageService $entityStorageService,
    ) {
    }

    protected function deleteAllFilesForEntity(?EntityWithFileInfo $entity): void
    {
        if ($entity === null) {
            return;
        }

        $this->entityStorageService->deleteAllFilesForEntity($entity);
    }

    abstract public function delete(AbstractDossier $dossier): void;

    public function deleteWithOverride(AbstractDossier $dossier): void
    {
        $this->delete($dossier);
    }
}
