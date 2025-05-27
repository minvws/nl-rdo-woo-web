<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Definition;

use App\Citation;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use App\Domain\Search\Query\Facet\DisplayValue\UntranslatedStringFacetDisplayValue;
use App\Domain\Search\Query\Facet\FacetDefinitionInterface;
use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\Filter\AndTermFilter;
use App\Service\Search\Query\Filter\FilterInterface;
use App\Service\Search\Query\Filter\SubTypesOnlyFilter;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class GroundsFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::GROUNDS;
    }

    public function getField(): ElasticField
    {
        return ElasticField::GROUNDS;
    }

    public function getRequestParameter(): string
    {
        return 'gnd';
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[]', $this->getRequestParameter());
    }

    public function getFilter(): FilterInterface
    {
        return new SubTypesOnlyFilter(new AndTermFilter());
    }

    public function getAggregationStrategy(): AggregationStrategyInterface
    {
        return new TermsAggregationStrategy(false);
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
        return TranslatedFacetDisplayValue::fromString('categories.category.grounds');
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        return UntranslatedStringFacetDisplayValue::fromString(
            $value . ' ' . Citation::toClassification($value),
        );
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return null;
    }
}
