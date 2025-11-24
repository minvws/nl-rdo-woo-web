<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class ResultFactory
{
    /**
     * @param iterable<SearchResultMapperInterface> $mappers
     */
    public function __construct(
        #[AutowireIterator('woo_platform.search.result_mapper')]
        private iterable $mappers,
    ) {
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
