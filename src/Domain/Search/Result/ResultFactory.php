<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use Jaytaph\TypeArray\TypeArray;

readonly class ResultFactory
{
    /**
     * @var iterable<SearchResultMapperInterface>
     */
    private iterable $mappers;

    /**
     * @param iterable<SearchResultMapperInterface> $mappers
     */
    public function __construct(iterable $mappers)
    {
        $this->mappers = $mappers;
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $type = ElasticDocumentType::from($hit->getString('[fields][type][0]'));
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($type)) {
                return $mapper->map($hit);
            }
        }

        throw SearchResultException::forUnsupportedDocumentType($type);
    }
}
