<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Dsl;

use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Sort\SortField;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ElasticQueryParameters
{
    /**
     * @var array<array-key,mixed>
     */
    private array $parameters = [];

    private readonly PropertyAccessor $propertyAccessor;

    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly SearchParameters $searchParameters,
    ) {
        $this->propertyAccessor = new PropertyAccessor();
    }

    public static function applyTo(QueryBuilder $queryBuilder, SearchParameters $searchParameters): self
    {
        return new self($queryBuilder, $searchParameters);
    }

    public function withSuggestParams(): self
    {
        if (! $this->searchParameters->hasQueryString()) {
            return $this;
        }

        $this->set(
            '[body][suggest]',
            [
                ElasticConfig::SUGGESTIONS_SEARCH_INPUT => [
                    'text' => $this->searchParameters->query,
                    'term' => [
                        'field' => 'content_for_suggestions',
                        'size' => 3,
                        'sort' => 'frequency',
                        'suggest_mode' => 'popular',
                        'string_distance' => 'jaro_winkler',
                    ],
                ],
            ],
        );

        return $this;
    }

    public function withDocvalueFields(): self
    {
        $this->set(
            '[body][docvalue_fields]',
            [
                ElasticField::TYPE->value,
                ElasticField::DOCUMENT_NR->value,
                ElasticField::DOCUMENT_PREFIX->value,
                ElasticField::DOSSIER_NR->value,
            ],
        );

        $this->set(
            '[body][_source]',
            false,
        );

        return $this;
    }

    public function withSortByScore(): self
    {
        $this->set(
            '[body][sort]',
            ['_score'],
        );

        return $this;
    }

    public function withUserDefinedSort(): self
    {
        if ($this->searchParameters->sortField === SortField::SCORE) {
            $this->withSortByScore();

            return $this;
        }

        $this->set(
            '[body][sort]',
            [[
                $this->searchParameters->sortField->value => [
                    'missing' => '_last',
                    'order' => $this->searchParameters->sortOrder->value,
                ],
            ]]
        );

        return $this;
    }

    public function set(string $path, mixed $value): void
    {
        $this->propertyAccessor->setValue($this->parameters, $path, $value);

        $this->queryBuilder->setParams($this->parameters);
    }
}
