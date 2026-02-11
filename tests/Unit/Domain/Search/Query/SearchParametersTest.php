<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query;

use ArrayIterator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Search\Query\Facet\Definition\DateFacet;
use Shared\Domain\Search\Query\Facet\Definition\InquiryDocumentsFacet;
use Shared\Domain\Search\Query\Facet\Definition\InquiryDossiersFacet;
use Shared\Domain\Search\Query\Facet\Input\DateFacetInput;
use Shared\Domain\Search\Query\Facet\Input\FacetInput;
use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchType;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Condition\QueryConditionBuilderInterface;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class SearchParametersTest extends UnitTestCase
{
    public function testWithFacetInput(): void
    {
        $parameters = new SearchParameters(
            facetInputs: $facetInputs = Mockery::mock(FacetInputCollection::class),
        );

        $facetKey = FacetKey::DEPARTMENT;
        $newFacetInput = Mockery::mock(FacetInput::class);

        $updatedFacetInputs = Mockery::mock(FacetInputCollection::class);
        $facetInputs->expects('withFacetInput')->with($facetKey, $newFacetInput)->andReturn($updatedFacetInputs);

        $newParameters = $parameters->withFacetInput($facetKey, $newFacetInput);

        self::assertNotSame($newParameters, $parameters);
        self::assertSame($updatedFacetInputs, $newParameters->facetInputs);
    }

    public function testWithQueryString(): void
    {
        $parameters = new SearchParameters(
            facetInputs: Mockery::mock(FacetInputCollection::class),
            query: 'foo',
        );

        $newParameters = $parameters->withQueryString('bar');

        self::assertEquals('foo', $parameters->query);
        self::assertEquals('bar', $newParameters->query);
    }

    public function testGetQueryParameters(): void
    {
        /** @var FacetInputInterface&MockInterface $enabledFacet */
        $enabledFacet = Mockery::mock(StringValuesFacetInput::class);
        $enabledFacet->expects('isNotActive')->andReturnFalse();
        $enabledFacet->expects('getRequestParameters')->andReturn(['x' => 'y']);

        /** @var FacetInputInterface&MockInterface $disabledFacet */
        $disabledFacet = Mockery::mock(StringValuesFacetInput::class);
        $disabledFacet->expects('isNotActive')->andReturnTrue();

        /** @var InquiryDocumentsFacet&MockInterface $documentInquiryFacet */
        $documentInquiryFacet = Mockery::mock(InquiryDocumentsFacet::class);
        $documentInquiryFacet->shouldReceive('isNotActive')->andReturnFalse();
        $documentInquiryFacet->shouldReceive('getRequestParameters')->andReturn(['doc1', 'doc2']);

        /** @var InquiryDossiersFacet&MockInterface $dossierInquiryFacet */
        $dossierInquiryFacet = Mockery::mock(InquiryDossiersFacet::class);
        $dossierInquiryFacet->shouldReceive('isNotActive')->andReturnFalse();
        $dossierInquiryFacet->shouldReceive('getRequestParameters')->andReturn(['doc1', 'doc2']);

        $facetInputCollection = Mockery::mock(FacetInputCollection::class);
        $facetInputCollection->shouldReceive('getIterator')->andReturn(new ArrayIterator([
            FacetKey::DEPARTMENT->value => $enabledFacet,
            FacetKey::PREFIXED_DOSSIER_NR->value => $disabledFacet,
            FacetKey::INQUIRY_DOCUMENTS->value => $documentInquiryFacet,
            FacetKey::INQUIRY_DOSSIERS->value => $dossierInquiryFacet,
        ]));

        $parameters = new SearchParameters(
            facetInputs: $facetInputCollection,
            offset: 13,
            query: 'foo',
            searchType: SearchType::DOSSIER,
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->getQueryParameters()
        );
    }

    public function testWithSort(): void
    {
        $facetInputCollection = Mockery::mock(FacetInputCollection::class);
        $facetInputCollection->shouldReceive('getIterator')->andReturn(new ArrayIterator([]));

        $parameters = new SearchParameters(
            facetInputs: $facetInputCollection,
            query: 'foo',
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->withSort(SortField::PUBLICATION_DATE, SortOrder::ASC)
        );
    }

    public function testIncludeWithoutDate(): void
    {
        $dateFacetInput = DateFacetInput::fromParameterBag(
            new DateFacet(),
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
        /** @var InquiryDocumentsFacet&MockInterface $documentInquiryFacet */
        $documentInquiryFacet = Mockery::mock(InquiryDocumentsFacet::class)->makePartial();
        $documentInquiryFacet->shouldReceive('isNotActive')->andReturnFalse();
        $documentInquiryFacet->shouldReceive('isActive')->andReturnTrue();
        $documentInquiryFacet->shouldReceive('getRequestParameters')->andReturn(['doc1', 'doc2']);

        /** @var InquiryDossiersFacet&MockInterface $dossierInquiryFacet */
        $dossierInquiryFacet = Mockery::mock(InquiryDossiersFacet::class)->makePartial();
        $dossierInquiryFacet->shouldReceive('isNotActive')->andReturnFalse();
        $dossierInquiryFacet->shouldReceive('isActive')->andReturnTrue();
        $dossierInquiryFacet->shouldReceive('getRequestParameters')->andReturn(['doc1', 'doc2']);

        $facetInputCollection = Mockery::mock(FacetInputCollection::class);
        $facetInputCollection->shouldReceive('getIterator')->andReturn(new ArrayIterator([
            FacetKey::INQUIRY_DOCUMENTS->value => $documentInquiryFacet,
            FacetKey::INQUIRY_DOSSIERS->value => $dossierInquiryFacet,
        ]));

        $parameters = new SearchParameters(
            facetInputs: $facetInputCollection,
            offset: 13,
            query: 'foo',
            searchType: SearchType::DOSSIER,
        );

        $queryConditions = Mockery::mock(QueryConditionBuilderInterface::class);
        $newParameters = $parameters->withBaseQueryConditions($queryConditions);

        $this->assertMatchesObjectSnapshot($newParameters);
        $this->assertSame($queryConditions, $newParameters->baseQueryConditions);
    }

    public function testHasActiveFacetsReturnsTrueForFirstActiveFacet(): void
    {
        $facetInputA = Mockery::mock(FacetInputInterface::class);
        $facetInputA->expects('isActive')->andReturnTrue();

        $facetInputB = Mockery::mock(FacetInputInterface::class);
        $facetInputB->shouldNotHaveBeenCalled();

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection($facetInputA, $facetInputB),
        );

        self::assertTrue($parameters->hasActiveFacets());
    }

    public function testHasActiveFacetsReturnsFalseWhenAllFacetsAreInactive(): void
    {
        $facetInputA = Mockery::mock(FacetInputInterface::class);
        $facetInputA->expects('isActive')->andReturnFalse();

        $facetInputB = Mockery::mock(FacetInputInterface::class);
        $facetInputB->expects('isActive')->andReturnFalse();

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection($facetInputA, $facetInputB),
        );

        self::assertFalse($parameters->hasActiveFacets());
    }

    public function testWithoutFacetFilter(): void
    {
        $dateFacetDefinition = new DateFacet();

        $dateFacetInput = DateFacetInput::fromParameterBag(
            $dateFacetDefinition,
            new ParameterBag([
                'dt' => [
                    'from' => '2021-01-15',
                    'to' => '2024-01-15',
                ],
            ])
        );

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection(...[
                FacetKey::DATE->value => $dateFacetInput,
            ]),
            query: 'foo',
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->withoutFacetFilter($dateFacetDefinition, 'from', '2021-01-15'),
        );
    }

    public function testWithoutFacetFilters(): void
    {
        $dateFacetDefinition = new DateFacet();

        $dateFacetInput = DateFacetInput::fromParameterBag(
            $dateFacetDefinition,
            new ParameterBag([
                'dt' => [
                    'from' => '2021-01-15',
                    'to' => '2024-01-15',
                ],
            ])
        );

        $parameters = new SearchParameters(
            facetInputs: new FacetInputCollection(...[
                FacetKey::DATE->value => $dateFacetInput,
            ]),
            query: 'foo',
        );

        $this->assertMatchesObjectSnapshot(
            $parameters->withoutFacetFilters(),
        );
    }
}
