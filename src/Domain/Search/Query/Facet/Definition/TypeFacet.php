<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Definition;

use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\Input\DocTypeValue;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use Shared\Service\Search\Query\Aggregation\TypeAggregationStrategy;
use Shared\Service\Search\Query\Filter\DocTypeFilter;
use Shared\Service\Search\Query\Filter\FilterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use function sprintf;

readonly class TypeFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::TYPE;
    }

    public function getField(): ElasticField
    {
        return ElasticField::TYPE;
    }

    public function getRequestParameter(): string
    {
        return 'doctype';
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[]', $this->getRequestParameter());
    }

    public function getFilter(): FilterInterface
    {
        return new DocTypeFilter();
    }

    public function getAggregationStrategy(): AggregationStrategyInterface
    {
        return new TypeAggregationStrategy();
    }

    public function getInput(ParameterBag $parameters): FacetInputInterface
    {
        return StringValuesFacetInput::fromParameterBag(
            $this,
            $parameters,
        );
    }

    public function displayActiveSelection(int|string $key, string $value): bool
    {
        $docType = DocTypeValue::fromString($value);

        // Only subtypes are displayed as pills
        return $docType->getSubType() !== null;
    }

    public function getTitle(int|string $key, string $value): FacetDisplayValueInterface
    {
        $docType = DocTypeValue::fromString($value);

        return TranslatedFacetDisplayValue::fromString('public.documents.type.' . $docType->getMainType());
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        $docTypeValue = DocTypeValue::fromString($value);
        if ($docTypeValue->getSubType() === null) {
            return TranslatedFacetDisplayValue::fromString(
                sprintf('public.documents.type.%s', $docTypeValue->getMainType()),
            );
        }

        return TranslatedFacetDisplayValue::fromString(
            sprintf('public.search.type.%s', $docTypeValue->getSubType()),
        );
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return TranslatedFacetDisplayValue::fromString(
            sprintf('public.search.type_description.%s', $value)
        );
    }
}
