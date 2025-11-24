<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier\Mapper;

use Shared\Domain\Publication\Dossier\AbstractDossier;

readonly class PrefixedDossierNr
{
    public static function forDossier(AbstractDossier $dossier): string
    {
        return $dossier->getDocumentPrefix() . '|' . $dossier->getDossierNr();
    }

    public static function withoutPrefix(string $input): string
    {
        $input = trim($input);

        $separatorPosition = strpos($input, '|');
        if ($separatorPosition === false) {
            return $input;
        }

        return substr($input, $separatorPosition + 1);
    }
}
