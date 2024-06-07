<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use Jaytaph\TypeArray\TypeArray;

interface DossierTypeSearchResultMapperInterface
{
    public function map(TypeArray $hit): ?ResultEntryInterface;
}
