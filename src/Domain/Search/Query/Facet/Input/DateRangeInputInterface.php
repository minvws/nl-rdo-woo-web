<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

interface DateRangeInputInterface extends FacetInputInterface
{
    public const DATE_FORMAT = 'Y-m-d';

    public function getDateRangeDate(): ?string;
}
