<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier;

use Shared\ApplicationId;

interface ProvidesDossierTypeSearchResultInterface
{
    public function getSearchResultViewModel(
        string $documentPrefix,
        string $dossierNumber,
        ApplicationId $applicationId,
    ): ?DossierTypeSearchResultInterface;
}
