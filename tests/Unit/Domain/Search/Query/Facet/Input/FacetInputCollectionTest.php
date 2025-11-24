<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query\Facet\Input;

use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Shared\Domain\Search\Query\Facet\Input\FacetInput;
use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Service\Search\Model\FacetKey;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\InvalidArgumentException;

#[Group('facet')]
#[Group('facetInput')]
final class FacetInputCollectionTest extends UnitTestCase
{
    public function testGetByFacetKey(): void
    {
        $facetInput = \Mockery::mock(FacetInput::class);

        $collection = new FacetInputCollection(...[
            FacetKey::PREFIXED_DOSSIER_NR->value => $facetInput,
        ]);

        self::assertSame($facetInput, $collection->getByFacetKey(FacetKey::PREFIXED_DOSSIER_NR));

        $this->expectException(InvalidArgumentException::class);
        $collection->getByFacetKey(FacetKey::DEPARTMENT);
    }

    public function testGetByFacetKeyThrowsExceptionForUnknownKey(): void
    {
        $collection = new FacetInputCollection();

        $this->expectException(InvalidArgumentException::class);
        $collection->getByFacetKey(FacetKey::DEPARTMENT);
    }

    public function testGetByFacetDefinition(): void
    {
        $facetInput = \Mockery::mock(FacetInput::class);

        $collection = new FacetInputCollection(...[
            FacetKey::PREFIXED_DOSSIER_NR->value => $facetInput,
        ]);

        $facetDefinition = \Mockery::mock(FacetDefinitionInterface::class);
        $facetDefinition->shouldReceive('getKey')->andReturn(FacetKey::PREFIXED_DOSSIER_NR);

        self::assertSame($facetInput, $collection->getByFacetDefinition($facetDefinition));
    }

    public function testGetByFacetDefinitionThrowsExceptionForUnknownDefinition(): void
    {
        $collection = new FacetInputCollection();

        $facetDefinition = \Mockery::mock(FacetDefinitionInterface::class);
        $facetDefinition->shouldReceive('getKey')->andReturn(FacetKey::DEPARTMENT);

        $this->expectException(InvalidArgumentException::class);
        $collection->getByFacetDefinition($facetDefinition);
    }

    public function testIterator(): void
    {
        $facetInputA = \Mockery::mock(FacetInput::class);
        $facetInputB = \Mockery::mock(FacetInput::class);

        $facets = [
            FacetKey::PREFIXED_DOSSIER_NR->value => $facetInputA,
            FacetKey::DEPARTMENT->value => $facetInputB,
        ];

        $collection = new FacetInputCollection(...$facets);

        self::assertEquals(
            $facets,
            iterator_to_array($collection),
        );
    }

    public function testWithFacetInput(): void
    {
        $facetInputA = \Mockery::mock(FacetInput::class);
        $facetInputB = \Mockery::mock(FacetInput::class);
        $facetInputC = \Mockery::mock(FacetInput::class);

        $facets = [
            FacetKey::PREFIXED_DOSSIER_NR->value => $facetInputA,
            FacetKey::DEPARTMENT->value => $facetInputB,
        ];

        $collection = new FacetInputCollection(...$facets);
        $result = $collection->withFacetInput(FacetKey::GROUNDS, $facetInputC);

        self::assertNotSame(
            $collection,
            $result
        );

        self::assertEquals(
            $facets,
            iterator_to_array($collection),
        );

        self::assertEquals(
            [
                FacetKey::PREFIXED_DOSSIER_NR->value => $facetInputA,
                FacetKey::DEPARTMENT->value => $facetInputB,
                FacetKey::GROUNDS->value => $facetInputC,
            ],
            iterator_to_array($result),
        );
    }
}
