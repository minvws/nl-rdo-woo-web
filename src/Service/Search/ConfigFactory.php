<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\FacetMappingService;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class ConfigFactory
{
    public const DEFAULT_PAGE_SIZE = 10;
    public const MIN_PAGE_SIZE = 1;
    public const MAX_PAGE_SIZE = 100;

    public function __construct(
        private readonly InquirySessionService $inquirySession,
        private readonly FacetMappingService $facetMapping,
    ) {
    }

    /**
     * This method will convert the HTTP request to a configuration object that can be used for search. It basically translates
     * the given facets and search terms.
     */
    public function createFromRequest(
        Request $request,
        bool $pagination = true,
        bool $aggregations = true,
    ): Config {
        $pageSize = $this->getPageSize($request);
        $pageNum = max($request->query->getInt('page', 1) - 1, 0);

        $facets = [];
        foreach ($this->facetMapping->getAll() as $facet) {
            if (! $request->query->has($facet->getQueryParam())) {
                continue;
            }

            // Make sure that $items is always an array
            $items = $request->query->all()[$facet->getQueryParam()];
            if (! is_array($items)) {
                $items = [$items];
            }

            // Url decode the strings but not numbers etc
            foreach ($items as $index => $item) {
                if (is_string($item)) {
                    $items[$index] = urldecode($item);
                }
            }

            $facets[$facet->getFacetKey()] = $items;
        }

        // Type is not a facet but must be set directly in the config
        $searchType = match ($request->query->get('type', '')) {
            Config::TYPE_DOCUMENT => Config::TYPE_DOCUMENT,
            Config::TYPE_DOSSIER => Config::TYPE_DOSSIER,
            default => Config::TYPE_ALL,
        };

        $documentInquiries = $this->getInquiries('dci', $request);
        $dossierInquiries = $this->getInquiries('dsi', $request);

        $query = $this->convertQueryStringToNegativeAndValues($request->query->getString('q', ''));

        $config = new Config(
            operator: Config::OPERATOR_PHRASE,
            facets: $facets,
            limit: $pageSize,
            offset: $pageNum * $pageSize,
            pagination: $pagination,
            aggregations: $aggregations,
            query: $query,
            searchType: $searchType,
            documentInquiries: $documentInquiries,
            dossierInquiries: $dossierInquiries,
            sortField: SortField::fromValue($request->query->getString('sort')),
            sortOrder: SortOrder::fromValue($request->query->getString('sortorder'))
        );

        return $config;
    }

    protected function getPageSize(Request $request): int
    {
        $pageSize = $request->query->getInt('size', self::DEFAULT_PAGE_SIZE);

        return max(min($pageSize, self::MAX_PAGE_SIZE), self::MIN_PAGE_SIZE);
    }

    /**
     * @return string[]
     */
    protected function getInquiries(string $paramKey, Request $request): array
    {
        if (! $request->query->has($paramKey)) {
            return [];
        }

        $validInquiries = $this->inquirySession->getInquiries();
        if (empty($validInquiries)) {
            return [];
        }

        $requestedInquiries = $request->query->all()[$paramKey];
        $requestedInquiries = is_array($requestedInquiries) ? array_values($requestedInquiries) : [$requestedInquiries];

        /** @var string[] $validatedInquiries */
        $validatedInquiries = array_intersect($requestedInquiries, $validInquiries);
        $validatedInquiries = array_values($validatedInquiries);

        return $validatedInquiries;
    }

    // Converts negative words into AND values by adding a + in front of them.
    public function convertQueryStringToNegativeAndValues(string $queryString): string
    {
        $queryString = trim($queryString);
        $newQueryString = '';

        $inPhrase = false;
        $inWord = false;
        for ($i = 0; $i != strlen($queryString); $i++) {
            if ($queryString[$i] == '"') {
                $inPhrase = ! $inPhrase;
            }

            if ($queryString[$i] == ' ' && ! $inPhrase) {
                $inWord = ! $inWord;
            }

            if (! $inPhrase && $queryString[$i] != ' ' && $queryString[$i] != '"' && $queryString[$i] != '+' && $queryString[$i] != '-') {
                $inWord = true;
            }

            if ($queryString[$i] == '-' && ! $inPhrase && ! $inWord) {
                $newQueryString .= '+';
            }
            $newQueryString .= $queryString[$i];
        }

        return $newQueryString;
    }
}
