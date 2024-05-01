<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\DossierType;

class IndexException extends \RuntimeException
{
    public static function forUnsupportedDossierType(DossierType $dossierType): self
    {
        return new self(sprintf(
            'Cannot index dossier of type %s',
            $dossierType->value,
        ));
    }
}
