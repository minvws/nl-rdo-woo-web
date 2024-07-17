<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use Jaytaph\TypeArray\TypeArray;

interface SearchResultMapperInterface
{
    public function supports(ElasticDocumentType $type): bool;

    public function map(TypeArray $hit): ?ResultEntryInterface;
}
