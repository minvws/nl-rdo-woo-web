<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Mockery;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchType;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Condition\ContentAccessConditionBuilder;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function json_encode;

final class ContentAccessConditionBuilderTest extends UnitTestCase
{
    private const array PUBLISHED_SUBJECT_FILTER = [
        'bool' => [
            'filter' => [
                ['terms' => ['type' => ['subject']]],
                ['term' => ['status' => ['value' => 'published']]],
            ],
        ],
    ];

    public function testPublishedSubjectsAreIncludedForTheCombinedSearch(): void
    {
        $query = $this->applyToQuery(SearchType::DEFAULT, inquiryDossiers: []);
        $builtQuery = $query->build();
        Assert::isArray($builtQuery);

        $rootBoolQuery = $builtQuery['bool'] ?? null;
        Assert::isArray($rootBoolQuery);
        $filters = $rootBoolQuery['filter'] ?? null;
        Assert::isArray($filters);
        $firstFilter = $filters[0] ?? null;
        Assert::isArray($firstFilter);
        $filterBoolQuery = $firstFilter['bool'] ?? null;
        Assert::isArray($filterBoolQuery);
        $shouldQueries = $filterBoolQuery['should'] ?? null;
        Assert::isArray($shouldQueries);

        self::assertContains(self::PUBLISHED_SUBJECT_FILTER, $shouldQueries);
    }

    public function testSubjectsAreExcludedWhenAnInquiryFilterIsActive(): void
    {
        $query = $this->applyToQuery(SearchType::DEFAULT, inquiryDossiers: ['inquiry-id']);

        self::assertStringNotContainsString('"subject"', $this->encode($query));
    }

    public function testSubjectsAreExcludedForTheDossierSearch(): void
    {
        $query = $this->applyToQuery(SearchType::DOSSIER, inquiryDossiers: []);

        self::assertStringNotContainsString('"subject"', $this->encode($query));
    }

    /**
     * @param list<string> $inquiryDossiers
     */
    private function applyToQuery(SearchType $searchType, array $inquiryDossiers): BoolQuery
    {
        $facetDefinition = Mockery::mock(FacetDefinitionInterface::class);
        $searchParameters = new SearchParameters(
            facetInputs: new FacetInputCollection(...[
                FacetKey::INQUIRY_DOSSIERS->value => new StringValuesFacetInput($facetDefinition, $inquiryDossiers),
                FacetKey::INQUIRY_DOCUMENTS->value => new StringValuesFacetInput($facetDefinition, []),
            ]),
            searchType: $searchType,
        );

        $query = new BoolQuery();
        new ContentAccessConditionBuilder()->applyToQuery(new FacetList([]), $searchParameters, $query);

        return $query;
    }

    private function encode(BoolQuery $query): string
    {
        return (string) json_encode($query->build());
    }
}
