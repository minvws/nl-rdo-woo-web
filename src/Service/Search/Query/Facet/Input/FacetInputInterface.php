<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

interface FacetInputInterface
{
    public function isActive(): bool;

    public function isNotActive(): bool;
}
