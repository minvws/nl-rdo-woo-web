<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;

interface ElasticDossierMapperInterface
{
    public function supports(AbstractDossier $dossier): bool;

    public function map(AbstractDossier $dossier): ElasticDocument;
}
