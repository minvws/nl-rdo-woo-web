<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier;

use Symfony\Component\Uid\Uuid;

readonly class AbstractDossierTypeSearchResult implements DossierTypeSearchResultInterface
{
    public function __construct(
        public Uuid $id,
        public string $dossierNr,
        public string $documentPrefix,
    ) {
    }
}
