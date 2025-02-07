<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Facet\Input\DateFacetInputInterface;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter will match dates in both dossiers and documents, even though both date fields are named differently and act differently.
 *
 * Searching for  date-from: 01-01-2020 will match dossiers that have a date_from field greater than or equal to 01-01-2020 and a date_to
 * field that is less than or equal to 01-01-2020. And it will find documents that have a date_from field that is equal to or greater than 01-01-2020.
 */
class PeriodFilter implements FilterInterface
{
    public function addToQuery(Facet $facet, BoolQuery $query, SearchParameters $searchParameters, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return;
        }

        if ($input->isWithoutDate()) {
            $this->handleWithoutDate($query);
        } elseif ($input->hasAnyPeriodFilterDates()) {
            $this->handleWithDate($query, $input);
        }
    }

    private function handleWithoutDate(BoolQuery $query): void
    {
        $query->addFilter(self::getWithoutDateQuery());
    }

    public static function getWithoutDateQuery(): BoolQuery
    {
        return Query::bool(should: [
            Query::bool(
                mustNot: [
                    Query::exists(field: 'date'),
                ],
                filter: [
                    Query::terms(
                        field: 'type',
                        values: ElasticDocumentType::getSubTypeValues(),
                    ),
                ],
            ),
            Query::bool(
                mustNot: [
                    Query::exists(field: 'date_to'),
                    Query::exists(field: 'date_from'),
                ],
                filter: [
                    Query::terms(
                        field: 'type',
                        values: ElasticDocumentType::getMainTypeValues(),
                    ),
                ],
            ),
        ])->setParams(['minimum_should_match' => 1]);
    }

    private function handleWithDate(BoolQuery $query, DateFacetInputInterface $input): void
    {
        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: 'type',
                                values: ElasticDocumentType::getSubTypeValues(),
                            ),
                            $this->getDocumentDateQuery($input),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::terms(
                                field: 'type',
                                values: ElasticDocumentType::getMainTypeValues(),
                            ),
                            $this->getDossierDateQuery($input),
                        ]
                    ),
                ],
            )->setParams(['minimum_should_match' => 1])
        );
    }

    private function getDocumentDateQuery(DateFacetInputInterface $input): QueryInterface
    {
        $query = Query::range('date');

        $toDate = $input->getPeriodFilterTo();
        if (! is_null($toDate)) {
            $query->lte($toDate);
        }

        $fromDate = $input->getPeriodFilterFrom();
        if (! is_null($fromDate)) {
            $query->gte($fromDate);
        }

        return $query;
    }

    private function getDossierDateQuery(DateFacetInputInterface $input): QueryInterface
    {
        $query = Query::range('date_range');

        $toDate = $input->getPeriodFilterTo();
        if (! is_null($toDate)) {
            $query->lte($toDate);
        }

        $fromDate = $input->getPeriodFilterFrom();
        if (! is_null($fromDate)) {
            $query->gte($fromDate);
        }

        // RangeQuery we use does not have a relation method, so we use params to manually set it
        $query->setParams(['relation' => 'intersects']);

        return $query;
    }

    public function getInput(Facet $facet): ?DateFacetInputInterface
    {
        if ($facet->input instanceof DateFacetInputInterface) {
            return $facet->input;
        }

        return null;
    }
}
