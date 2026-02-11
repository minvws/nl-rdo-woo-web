<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Definition;

use DateTimeImmutable;
use IntlDateFormatter;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\DisplayValue\TranslatedFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\DisplayValue\UntranslatedStringFacetDisplayValue;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\Input\DateFacetInput;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use Shared\Service\Search\Query\Aggregation\DateTermAggregationStrategy;
use Shared\Service\Search\Query\Filter\FilterInterface;
use Shared\Service\Search\Query\Filter\PeriodFilter;
use Symfony\Component\HttpFoundation\ParameterBag;

use function sprintf;

readonly class DateFacet implements FacetDefinitionInterface
{
    public function getKey(): FacetKey
    {
        return FacetKey::DATE;
    }

    public function getField(): ElasticField
    {
        return ElasticField::DATE_FILTER;
    }

    public function getRequestParameter(): string
    {
        return 'dt';
    }

    public function getQueryParameter(string $key): string
    {
        return sprintf('%s[%s]', $this->getRequestParameter(), $key);
    }

    public function getFilter(): FilterInterface
    {
        return new PeriodFilter();
    }

    public function getAggregationStrategy(): AggregationStrategyInterface
    {
        return new DateTermAggregationStrategy();
    }

    public function getInput(ParameterBag $parameters): FacetInputInterface
    {
        return DateFacetInput::fromParameterBag(
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
        $translationKey = match ($key) {
            DateFacetInput::FROM => 'categories.category.date.from',
            DateFacetInput::TO => 'categories.category.date.to',
            DateFacetInput::WITHOUT_DATE => 'categories.category.date.without_date',
            default => '',
        };

        return TranslatedFacetDisplayValue::fromString($translationKey);
    }

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface
    {
        if ($key === DateFacetInput::WITHOUT_DATE && $value === '1') {
            return TranslatedFacetDisplayValue::fromString('categories.category.date.without_date.value');
        }

        return UntranslatedStringFacetDisplayValue::fromString(
            IntlDateFormatter::formatObject(new DateTimeImmutable($value), 'd MMMM YYYY', 'nl_NL'),
        );
    }

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface
    {
        return null;
    }
}
