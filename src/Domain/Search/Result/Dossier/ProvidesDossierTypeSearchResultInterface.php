<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier;

use Shared\Service\Security\ApplicationMode\ApplicationMode;

interface ProvidesDossierTypeSearchResultInterface
{
    public function getSearchResultViewModel(string $prefix, string $dossierNr, ApplicationMode $mode): ?DossierTypeSearchResultInterface;
}
