<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\ElasticDocument;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.index.dossier_mapper')]
interface ElasticDossierMapperInterface
{
    public function supports(AbstractDossier $dossier): bool;

    public function map(AbstractDossier $dossier): ElasticDocument;
}
