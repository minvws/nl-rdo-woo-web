<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Definition;

use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatableFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use Shared\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use Shared\Service\Search\Query\Filter\FilterInterface;
use Shared\Service\Search\Query\Filter\OrTermFilter;
use Shared\Service\Search\Query\Filter\SubTypesOnlyFilter;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class SourceFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::SOURCE;
    }

    public function getField(): ElasticField
    {
        return ElasticField::SOURCE_TYPE;
    }

    public function getRequestParameter(): string
    {
        return 'src';
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[]', $this->getRequestParameter());
    }

    public function getFilter(): FilterInterface
    {
        return new SubTypesOnlyFilter(new OrTermFilter());
    }

    public function getAggregationStrategy(): AggregationStrategyInterface
    {
        return new TermsAggregationStrategy();
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
        return true;
    }

    public function getTitle(int|string $key, string $value): FacetDisplayValueInterface
    {
        return TranslatedFacetDisplayValue::fromString('categories.category.source_file');
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        return TranslatableFacetDisplayValue::fromTranslatable(
            SourceType::create($value)
        );
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return null;
    }
}
