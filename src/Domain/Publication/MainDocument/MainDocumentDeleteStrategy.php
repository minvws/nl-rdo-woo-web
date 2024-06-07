<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;

readonly class MainDocumentDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithMainDocument) {
            return;
        }

        $this->deleteFileForEntity($dossier->getDocument());
    }
}
