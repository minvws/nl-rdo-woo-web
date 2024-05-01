<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\AbstractDossierMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;

readonly class CovenantMapper
{
    public function __construct(
        private AbstractDossierMapper $abstractDossierMapper,
    ) {
    }

    public function map(Covenant $dossier): ElasticDocument
    {
        return new ElasticDocument(
            ElasticDocumentType::COVENANT,
            $this->abstractDossierMapper->mapCommonFields($dossier),
        );
    }
}
