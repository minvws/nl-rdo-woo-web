<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Definition;

use App\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
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
use App\Service\Search\Query\Filter\FilterInterface;
use App\Service\Search\Query\Filter\OrTermFilter;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class DepartmentFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::DEPARTMENT;
    }

    public function getField(): ElasticField
    {
        return ElasticField::DEPARTMENT_NAMES;
    }

    public function getRequestParameter(): string
    {
        return 'dep';
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[]', $this->getRequestParameter());
    }

    public function getFilter(): FilterInterface
    {
        return new OrTermFilter();
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
        return TranslatedFacetDisplayValue::fromString('categories.category.department');
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        $department = DepartmentFieldMapper::fromString($value);

        return UntranslatedStringFacetDisplayValue::fromString($department->getValue());
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        $department = DepartmentFieldMapper::fromString($value);

        return UntranslatedStringFacetDisplayValue::fromString($department->getDescription());
    }
}
