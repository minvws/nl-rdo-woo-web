<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

interface ProvidesDossierTypeSearchResultInterface
{
    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?DossierTypeSearchResultInterface;
}
