<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Erichard\ElasticQueryBuilder\Query\RangeQuery;
use Erichard\ElasticQueryBuilder\Query\TermQuery;

/**
 * This filter will match dates in both dossiers and documents, even though both date fields are named differently and act differently.
 *
 * Searching for  date-from: 01-01-2020 will match dossiers that have a date_from field greater than or equal to 01-01-2020 and a date_to
 * field that is less than or equal to 01-01-2020. And it will find documents that have a date_from field that is equal to or greater than 01-01-2020.
 */
class PeriodFilter implements FilterInterface
{
    public function addToQuery(FacetDefinition $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        $values = $config->getFacetValues($facet);
        $fromDate = $this->asDate($values['from'] ?? null);
        $toDate = $this->asDate($values['to'] ?? null);

        if ($fromDate == null && $toDate == null) {
            return;
        }

        $query->addFilter(
            new BoolQuery(
                should: [
                    new BoolQuery(
                        filter: [
                            new TermQuery(
                                field: 'type',
                                value: Config::TYPE_DOCUMENT,
                            ),
                            $this->getDocumentDateQuery($fromDate, $toDate),
                        ]
                    ),
                    new BoolQuery(
                        filter: [
                            new TermQuery(
                                field: 'type',
                                value: Config::TYPE_DOSSIER,
                            ),
                            $this->getDossierDateQuery($fromDate, $toDate),
                        ]
                    ),
                ],
                params: ['minimum_should_match' => 1],
            )
        );
    }

    private function getDocumentDateQuery(?\DateTimeImmutable $fromDate, ?\DateTimeImmutable $toDate): RangeQuery
    {
        $query = new RangeQuery('date');
        if ($toDate) {
            $query->lte($toDate->format('Y-m-d'));
        }
        if ($fromDate) {
            $query->gte($fromDate->format('Y-m-d'));
        }

        return $query;
    }

    private function getDossierDateQuery(?\DateTimeImmutable $fromDate, ?\DateTimeImmutable $toDate): BoolQuery
    {
        $rangeFromQuery = new RangeQuery('date_from');
        if ($fromDate) {
            $rangeFromQuery->gte($fromDate->format('Y-m-d'));
        }
        if ($toDate) {
            $rangeFromQuery->lte($toDate->format('Y-m-d'));
        }

        $rangeToQuery = new RangeQuery('date_to');
        if ($fromDate) {
            $rangeToQuery->gte($fromDate->format('Y-m-d'));
        }
        if ($toDate) {
            $rangeToQuery->lte($toDate->format('Y-m-d'));
        }

        $rangeOverspanQuery = null;
        if ($fromDate && $toDate) {
            $fromQuery = new RangeQuery('date_from');
            $fromQuery->lt($fromDate->format('Y-m-d'));

            $toQuery = new RangeQuery('date_to');
            $toQuery->gt($toDate->format('Y-m-d'));

            $rangeOverspanQuery = new BoolQuery();
            $rangeOverspanQuery->addMust($fromQuery);
            $rangeOverspanQuery->addMust($toQuery);
        }

        $query = new BoolQuery();
        $query->addShould($rangeFromQuery);
        $query->addShould($rangeToQuery);
        if ($rangeOverspanQuery) {
            $query->addShould($rangeOverspanQuery);
        }
        $query->setParams(['minimum_should_match' => 1]);

        return $query;
    }

    private function asDate(mixed $value): ?\DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
