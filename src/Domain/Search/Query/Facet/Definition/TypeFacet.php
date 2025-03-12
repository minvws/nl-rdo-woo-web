<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Definition;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use App\Domain\Search\Query\Facet\FacetDefinitionInterface;
use App\Domain\Search\Query\Facet\Input\DocTypeValue;
use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Aggregation\TypeAggregationStrategy;
use App\Service\Search\Query\Filter\DocTypeFilter;
use App\Service\Search\Query\Filter\FilterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

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
