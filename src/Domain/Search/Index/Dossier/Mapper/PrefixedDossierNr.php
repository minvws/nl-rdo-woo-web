<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;

readonly class PrefixedDossierNr
{
    public static function forDossier(AbstractDossier $dossier): string
    {
        return $dossier->getDocumentPrefix() . '|' . $dossier->getDossierNr();
    }
}
