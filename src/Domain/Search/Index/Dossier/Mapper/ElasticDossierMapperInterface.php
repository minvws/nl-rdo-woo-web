<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;

interface ElasticDossierMapperInterface
{
    public function supports(AbstractDossier $dossier): bool;

    public function map(AbstractDossier $dossier): ElasticDocument;
}
