<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

interface StringValuesFacetInputInterface extends FacetInputInterface
{
    /**
     * @return list<string>
     */
    public function getStringValues(): array;
}
