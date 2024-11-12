<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\FileProvider;

use App\Domain\Publication\Dossier\AbstractDossier;

class DossierFileNotFoundException extends \RuntimeException
{
    public static function forEntity(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
    ): self {
        return new self(
            sprintf(
                'No entity with id %s found for dossier %s (DossierFileType=%s)',
                $id,
                $dossier->getId()->toRfc4122(),
                $type->value,
            ),
        );
    }

    public static function forDossierTypeMismatch(
        DossierFileType $type,
        AbstractDossier $dossier,
    ): self {
        return new self(
            sprintf(
                'Cannot load dossier file of type %s for dossier %s (dossier type mismatch)',
                $type->value,
                $dossier->getId()->toRfc4122(),
            ),
        );
    }
}
