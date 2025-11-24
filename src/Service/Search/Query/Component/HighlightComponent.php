<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Component;

use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticPath;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Query;

readonly class HighlightComponent
{
    public const HL_START = '[[hl_start]]';
    public const HL_END = '[[hl_end]]';

    public function apply(QueryBuilder $queryBuilder, SearchParameters $searchParameters): void
    {
        if (! $searchParameters->hasQueryString()) {
            return;
        }

        // Highlighting uses a 'clean' query with additional filters like status.
        // This is very important, otherwise filter values like 'document' and statuses will be highlighted in content.
        $query = Query::simpleQueryString(
            fields: [
                ElasticField::TITLE->value,
                ElasticField::SUMMARY->value,
                ElasticPath::dossiersSummary()->value,
                ElasticPath::dossiersTitle()->value,
                ElasticPath::pagesContent()->value,
            ],
            query: $searchParameters->query,
        )->setDefaultOperator($searchParameters->operator->value);

        $queryBuilder->setHighlight([
            'max_analyzed_offset' => 1000000,
            'pre_tags' => [self::HL_START],
            'post_tags' => [self::HL_END],
            'fields' => [
                // Document object
                ElasticPath::pagesContent()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticPath::dossiersTitle()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticPath::dossiersSummary()->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                // Dossier object
                ElasticField::TITLE->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
                ElasticField::SUMMARY->value => [
                    'fragment_size' => 50,
                    'number_of_fragments' => 5,
                    'type' => 'unified',
                ],
            ],
            'require_field_match' => true,
            'highlight_query' => $query->build(),
        ]);
    }
}
