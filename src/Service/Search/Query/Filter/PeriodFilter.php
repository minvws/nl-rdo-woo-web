<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
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
    public function addToQuery(Facet $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $input = $this->getInput($facet);
        if (is_null($input)) {
            return;
        }

        switch (true) {
            case $input->isWithoutDate():
                $this->handleWithoutDate($query);
                break;
            case $input->hasAnyPeriodFilterDates():
                $this->handleWithDate($query, $input);
                break;
        }
    }

    private function handleWithoutDate(BoolQuery $query): void
    {
        $query->addFilter(
            Query::bool(should: [
                Query::bool(
                    filter: [
                        Query::term(
                            field: 'type',
                            value: Config::TYPE_DOCUMENT,
                        ),
                    ],
                    mustNot: [
                        Query::exists(field: 'date'),
                    ],
                ),
                Query::bool(
                    filter: [
                        Query::term(
                            field: 'type',
                            value: Config::TYPE_DOSSIER,
                        ),
                    ],
                    mustNot: [
                        Query::exists(field: 'date_to'),
                        Query::exists(field: 'date_from'),
                    ],
                ),
            ])->setParams(['minimum_should_match' => 1]),
        );
    }

    private function handleWithDate(BoolQuery $query, DateFacetInputInterface $input): void
    {
        $query->addFilter(
            Query::bool(
                should: [
                    Query::bool(
                        filter: [
                            Query::term(
                                field: 'type',
                                value: Config::TYPE_DOCUMENT,
                            ),
                            $this->getDocumentDateQuery($input),
                        ]
                    ),
                    Query::bool(
                        filter: [
                            Query::term(
                                field: 'type',
                                value: Config::TYPE_DOSSIER,
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
