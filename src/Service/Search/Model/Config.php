<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

use App\Service\Search\Query\Facet\FacetDefinition;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;

class Config
{
    public const OPERATOR_PHRASE = 'phrase';
    public const OPERATOR_AND = 'and';
    public const OPERATOR_OR = 'or';

    public const TYPE_DOSSIER = 'dossier';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_ALL = 'all';

    /**
     * @param array<string, mixed[]> $facets
     * @param string[]               $documentInquiries
     * @param string[]               $dossierInquiries
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        public readonly string $operator = self::OPERATOR_OR,
        public readonly array $facets = [],
        public readonly int $limit = 0,
        public readonly int $offset = 0,
        public readonly bool $pagination = true,
        public readonly bool $aggregations = true,
        public readonly string $query = '',
        public readonly string $searchType = self::TYPE_ALL,
        public readonly array $documentInquiries = [],
        public readonly array $dossierInquiries = [],
        public readonly SortField $sortField = SortField::SCORE,
        public readonly SortOrder $sortOrder = SortOrder::DESC,
    ) {
    }

    public function hasFacetValues(FacetDefinition $facet): bool
    {
        return array_key_exists($facet->getFacetKey(), $this->facets) && count($this->facets[$facet->getFacetKey()]) > 0;
    }

    /**
     * @return array|mixed[]
     */
    public function getFacetValues(FacetDefinition $facet): array
    {
        return $this->facets[$facet->getFacetKey()] ?? [];
    }
}
