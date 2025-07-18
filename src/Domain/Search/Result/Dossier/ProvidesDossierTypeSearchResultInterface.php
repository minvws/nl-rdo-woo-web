<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier;

use App\Service\Security\ApplicationMode\ApplicationMode;

interface ProvidesDossierTypeSearchResultInterface
{
    public function getSearchResultViewModel(string $prefix, string $dossierNr, ApplicationMode $mode): ?DossierTypeSearchResultInterface;
}
