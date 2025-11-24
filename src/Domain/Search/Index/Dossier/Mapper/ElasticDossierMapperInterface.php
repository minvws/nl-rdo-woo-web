<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Dossier\Mapper;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\Index\ElasticDocument;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.search.index.dossier_mapper')]
interface ElasticDossierMapperInterface
{
    public function supports(AbstractDossier $dossier): bool;

    public function map(AbstractDossier $dossier): ElasticDocument;
}
