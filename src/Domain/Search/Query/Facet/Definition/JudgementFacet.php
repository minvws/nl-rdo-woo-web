<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Definition;

use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\Facet\DisplayValue\TranslatableFacetDisplayValue;
use App\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use App\Domain\Search\Query\Facet\FacetDefinitionInterface;
use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Aggregation\TermsAggregationStrategy;
use App\Service\Search\Query\Filter\FilterInterface;
use App\Service\Search\Query\Filter\OrTermFilter;
use App\Service\Search\Query\Filter\SubTypesOnlyFilter;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class JudgementFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::JUDGEMENT;
    }

    public function getField(): ElasticField
    {
        return ElasticField::JUDGEMENT;
    }

    public function getRequestParameter(): string
    {
        return 'jdg';
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
        return TranslatedFacetDisplayValue::fromString('categories.category.judgement');
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        return TranslatableFacetDisplayValue::fromTranslatable(
            DecisionType::from($value),
        );
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return null;
    }
}
