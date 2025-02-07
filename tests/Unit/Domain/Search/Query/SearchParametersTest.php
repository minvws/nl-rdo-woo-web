<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Condition\QueryConditions;
use App\Service\Search\Query\Facet\Input\DateFacetInput;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Facet\Input\FacetInputInterface;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class SearchParametersTest extends UnitTestCase
{
    public function testWithFacetInput(): void
    {
        $parameters = new SearchParameters(
            facetInputs: $facetInputs = \Mockery::mock(FacetInputCollection::class),
        );

        $facetKey = FacetKey::DEPARTMENT;
        $newFacetInput = \Mockery::mock(FacetInput::class);

        $updatedFacetInputs = \Mockery::mock(FacetInputCollection::class);
        $facetInputs->expects('withFacetInput')->with($facetKey, $newFacetInput)->andReturn($updatedFacetInputs);

        $newParameters = $parameters->withFacetInput($facetKey, $newFacetInput);

        self::assertNotSame($newParameters, $parameters);
        self::assertSame($updatedFacetInputs, $newParameters->facetInputs);
    }

    public function testWithQueryString(): void
    {
        $parameters = new SearchParameters(
            facetInputs: \Mockery::mock(FacetInputCollection::class),
            query: 'foo',
        );

        $newParameters = $parameters->withQueryString('bar');

        self::assertEquals('foo', $parameters->query);
        self::assertEquals('bar', $newParameters->query);
    }

    public function testGetQueryParameters(): void
    {
        /** @var FacetInputInterface&MockInterface $enabledFacet */
        $enabledFacet = \Mockery::mock(StringValuesFacetInput::class);
        $enabledFacet->expects('isNotActive')->andReturnFalse();
        $enabledFacet->expects('getRequestParameters')->andReturn(['x' => 'y']);

        /** @var FacetInputInterface&MockInterface $disabledFacet */
        $disabledFacet = \Mockery::mock(StringValuesFacetInput::class);
        $disabledFacet->expects('isNotActive')->andReturnTrue();

        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);
        $facetInputCollection->expects('getIterator')->andReturn(new \ArrayIterator([
            FacetKey::DEPARTMENT->value => $enabledFacet,
            FacetKey::PREFIXED_DOSSIER_NR->value => $disabledFacet,
        ]));

        $parameters = new SearchParameters(
            facetInputs: $facetInputCollection,
            offset: 13,
            query: 'foo',
            searchType: SearchType::DOSSIER,
            documentInquiries: ['doc1', 'doc2'],
            dossierInquiries: ['dos1', 'dos2'],
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->getQueryParameters()
        );
    }

    public function testWithSort(): void
    {
        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection(),
            query: 'foo',
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->withSort(SortField::PUBLICATION_DATE, SortOrder::ASC)
        );
    }

    public function testIncludeWithoutDate(): void
    {
        $dateFacetInput = DateFacetInput::fromParameterBag(
            FacetKey::DATE,
            new ParameterBag([
                'dt' => ['from' => '2021-01-15'],
            ])
        );

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection(...[
                FacetKey::DATE->value => $dateFacetInput,
            ]),
            query: 'foo',
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->includeWithoutDate()
        );
    }

    public function testWithBaseQuery(): void
    {
        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection(),
            offset: 13,
            query: 'foo',
            searchType: SearchType::DOSSIER,
            documentInquiries: ['doc1', 'doc2'],
            dossierInquiries: ['dos1', 'dos2'],
        );

        $queryConditions = \Mockery::mock(QueryConditions::class);
        $newParameters = $parameters->withBaseQueryConditions($queryConditions);

        $this->assertMatchesObjectSnapshot($newParameters);
        $this->assertSame($queryConditions, $newParameters->baseQueryConditions);
    }

    public function testHasActiveFacetsReturnsTrueForFirstActiveFacet(): void
    {
        $facetInputA = \Mockery::mock(FacetInputInterface::class);
        $facetInputA->expects('isActive')->andReturnTrue();

        $facetInputB = \Mockery::mock(FacetInputInterface::class);
        $facetInputB->shouldNotHaveBeenCalled();

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection($facetInputA, $facetInputB),
        );

        self::assertTrue($parameters->hasActiveFacets());
    }

    public function testHasActiveFacetsReturnsFalseWhenAllFacetsAreInactive(): void
    {
        $facetInputA = \Mockery::mock(FacetInputInterface::class);
        $facetInputA->expects('isActive')->andReturnFalse();

        $facetInputB = \Mockery::mock(FacetInputInterface::class);
        $facetInputB->expects('isActive')->andReturnFalse();

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection($facetInputA, $facetInputB),
        );

        self::assertFalse($parameters->hasActiveFacets());
    }
}
