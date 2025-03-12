<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Input;

abstract readonly class FacetInput implements FacetInputInterface
{
    abstract public function isActive(): bool;

    public function isNotActive(): bool
    {
        return ! $this->isActive();
    }
}
