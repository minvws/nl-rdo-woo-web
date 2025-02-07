<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

interface DateRangeInputInterface extends FacetInputInterface
{
    public const DATE_FORMAT = 'Y-m-d';

    public function getDateRangeDate(): ?string;
}
