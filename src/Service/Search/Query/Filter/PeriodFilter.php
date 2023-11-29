<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetDefinition;
use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
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

    private function getDocumentDateQuery(?\DateTimeImmutable $fromDate, ?\DateTimeImmutable $toDate): QueryInterface
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

    private function getDossierDateQuery(?\DateTimeImmutable $fromDate, ?\DateTimeImmutable $toDate): QueryInterface
    {
        $query = new RangeQuery('date_range');
        if ($fromDate) {
            $query->gte($fromDate->format('Y-m-d'));
        }
        if ($toDate) {
            $query->lte($toDate->format('Y-m-d'));
        }
        // RangeQuery we use does not have a relation method, so we use params to manually set it
        $query->setParams(['relation' => 'intersects']);

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
