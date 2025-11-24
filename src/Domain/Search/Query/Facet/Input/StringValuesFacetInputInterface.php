<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

interface StringValuesFacetInputInterface extends FacetInputInterface
{
    /**
     * @return list<string>
     */
    public function getStringValues(): array;

    public function contains(string $value): bool;
}
