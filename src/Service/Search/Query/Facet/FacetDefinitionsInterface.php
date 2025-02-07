<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

interface FacetDefinitionsInterface
{
    /**
     * @return list<FacetDefinition>
     */
    public function getDefinitions(): array;
}
