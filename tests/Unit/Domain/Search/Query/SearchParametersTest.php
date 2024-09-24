<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Facet\Input\FacetInputInterface;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class SearchParametersTest extends MockeryTestCase
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
            FacetKey::DOSSIER_NR->value => $disabledFacet,
        ]));

        $parameters = new SearchParameters(
            facetInputs: $facetInputCollection,
            query: 'foo',
            searchType: SearchType::DOSSIER,
        );

        self::assertEquals(
            [
                FacetKey::DEPARTMENT->getParamName() => ['x' => 'y'],
                'type' => SearchType::DOSSIER->value,
                'q' => 'foo',
            ],
            $parameters->getQueryParameters()->all(),
        );
    }
}
