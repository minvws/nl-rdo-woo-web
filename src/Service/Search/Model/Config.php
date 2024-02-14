<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;

final readonly class Config
{
    public const OPERATOR_PHRASE = 'phrase';
    public const OPERATOR_AND = 'and';
    public const OPERATOR_OR = 'or';

    public const TYPE_DOSSIER = 'dossier';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_ALL = 'all';

    /**
     * @param array<key-of<FacetKey>,FacetInput> $facetInputs
     * @param list<string>                       $documentInquiries
     * @param list<string>                       $dossierInquiries
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        public array $facetInputs,
        public string $operator = self::OPERATOR_AND,
        public int $limit = 0,
        public int $offset = 0,
        public bool $pagination = true,
        public bool $aggregations = true,
        public string $query = '',
        public string $searchType = self::TYPE_ALL,
        public array $documentInquiries = [],
        public array $dossierInquiries = [],
        public SortField $sortField = SortField::SCORE,
        public SortOrder $sortOrder = SortOrder::DESC,
    ) {
    }
}
