<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\FileProvider;

use Shared\Domain\Publication\Dossier\AbstractDossier;

class DossierFileNotFoundException extends \RuntimeException
{
    public static function forEntity(
        DossierFileType $type,
        AbstractDossier $dossier,
        string $id,
        ?\Throwable $previous = null,
    ): self {
        return new self(
            sprintf(
                'No entity with id %s found for dossier %s (DossierFileType=%s)',
                $id,
                $dossier->getId()->toRfc4122(),
                $type->value,
            ),
            previous: $previous,
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
