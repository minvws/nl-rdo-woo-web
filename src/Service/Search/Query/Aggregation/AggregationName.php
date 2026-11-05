<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Aggregation;

final class AggregationName
{
    public const string PUBLICATION = 'publication';
    public const string UNIQUE_DOSSIERS = 'unique_dossiers';
    public const string ALL = 'all';
    public const string FACET_BASE_FILTER = 'facet-base-filter';
    public const string FACET_FILTER_PREFIX = 'facet-filter-';
    public const string MAIN_TYPES_ONLY = 'main_types_only';
}
