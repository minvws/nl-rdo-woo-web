<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

interface DateFacetInputInterface extends FacetInputInterface
{
    public const DATE_FORMAT = 'Y-m-d';

    public function isWithoutDate(): bool;

    public function hasAnyPeriodFilterDates(): bool;

    public function getPeriodFilterFrom(): ?string;

    public function getPeriodFilterTo(): ?string;
}
