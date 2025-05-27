<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class ResultFactory
{
    /**
     * @param iterable<SearchResultMapperInterface> $mappers
     */
    public function __construct(private iterable $mappers)
    {
    }

    public function map(TypeArray $hit, ApplicationMode $mode): ?ResultEntryInterface
    {
        $type = ElasticDocumentType::from($hit->getString('[fields][type][0]'));
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($type)) {
                return $mapper->map($hit, $mode);
            }
        }

        throw SearchResultException::forUnsupportedDocumentType($type);
    }
}
