<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Result;

use App\Citation;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticField;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Result\AggregationMapper;
use App\SourceType;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AggregationMapperTest extends MockeryTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private FacetInputFactory&MockInterface $facetInputFactory;
    private AggregationMapper $mapper;

    public function setUp(): void
    {
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->facetInputFactory = \Mockery::mock(FacetInputFactory::class);

        $this->mapper = new AggregationMapper($this->translator, $this->facetInputFactory);
    }

    public function testMapGrounds(): void
    {
        $searchParameters = \Mockery::mock(SearchParameters::class);

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::GROUNDS, '5.1.1a')
            ->andReturn($facetInputA = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::GROUNDS, $facetInputA)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::GROUNDS, 'foo.bar')
            ->andReturn($facetInputB = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::GROUNDS, $facetInputB)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $result = $this->mapper->map(
            FacetKey::GROUNDS->value,
            [
                new TypeArray(['key' => Citation::DUBBEL, 'doc_count' => 123]),
                new TypeArray(['key' => '5.1.1a', 'doc_count' => 456]),
                new TypeArray(['key' => 'foo.bar', 'doc_count' => 789]),
            ],
            $searchParameters,
        );

        // Citation 'dubbel' should be skipped.
        self::assertCount(2, $result->getEntries());

        // Citation '5.1.1a' translated.
        self::assertEquals('5.1.1a', $result->getEntries()[0]->getKey());
        self::assertEquals(456, $result->getEntries()[0]->getCount());

        // Unknown citations outputted as-is.
        self::assertEquals('foo.bar', $result->getEntries()[1]->getKey());
        self::assertEquals(789, $result->getEntries()[1]->getCount());
    }

    public function testMapDepartment(): void
    {
        $searchParameters = \Mockery::mock(SearchParameters::class);

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::DEPARTMENT, 'FOO|bar baz')
            ->andReturn($facetInputA = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::DEPARTMENT, $facetInputA)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::DEPARTMENT, 'BAR|baz foo')
            ->andReturn($facetInputB = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::DEPARTMENT, $facetInputB)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $result = $this->mapper->map(
            FacetKey::DEPARTMENT->value,
            [
                new TypeArray(['key' => 'FOO|bar baz', 'doc_count' => 456]),
                new TypeArray(['key' => 'BAR|baz foo', 'doc_count' => 789]),
            ],
            $searchParameters,
        );

        self::assertCount(2, $result->getEntries());

        self::assertEquals('FOO|bar baz', $result->getEntries()[0]->getKey());
        self::assertEquals(456, $result->getEntries()[0]->getCount());

        self::assertEquals('BAR|baz foo', $result->getEntries()[1]->getKey());
        self::assertEquals(789, $result->getEntries()[1]->getCount());
    }

    public function testMapType(): void
    {
        $searchParameters = \Mockery::mock(SearchParameters::class);

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'dossier')
            ->andReturn($facetInputA = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputA)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'covenant')
            ->andReturn($facetInputB = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputB)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $result = $this->mapper->map(
            FacetKey::TYPE->value,
            [
                new TypeArray(['key' => 'dossier', 'doc_count' => 456]),
                new TypeArray(['key' => 'covenant', 'doc_count' => 789]),
            ],
            $searchParameters,
        );

        self::assertCount(2, $result->getEntries());

        self::assertEquals('dossier', $result->getEntries()[0]->getKey());
        self::assertEquals(456, $result->getEntries()[0]->getCount());

        self::assertEquals('covenant', $result->getEntries()[1]->getKey());
        self::assertEquals(789, $result->getEntries()[1]->getCount());
    }

    public function testMapSource(): void
    {
        $searchParameters = \Mockery::mock(SearchParameters::class);

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::SOURCE, SourceType::SOURCE_EMAIL)
            ->andReturn($facetInputB = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::SOURCE, $facetInputB)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $prefix = 'public.documents.file_type.';
        $this->translator->expects('trans')->with($prefix . SourceType::SOURCE_EMAIL)->andReturn('foo-bar');

        $result = $this->mapper->map(
            FacetKey::SOURCE->value,
            [
                new TypeArray(['key' => SourceType::SOURCE_EMAIL, 'doc_count' => 123]),
            ],
            $searchParameters,
        );

        self::assertCount(1, $result->getEntries());

        self::assertEquals(SourceType::SOURCE_EMAIL, $result->getEntries()[0]->getKey());
        self::assertEquals(123, $result->getEntries()[0]->getCount());
    }

    public function testMapToplevelType(): void
    {
        $searchParameters = \Mockery::mock(SearchParameters::class);

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'dossier', 'dossier.publication', 'dossier.document', 'dossier.attachment')
            ->andReturn($facetInputA = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputA)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'dossier')
            ->andReturn($facetInputB = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputB)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'document')
            ->andReturn($facetInputC = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputC)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $this->facetInputFactory
            ->expects('createStringFacetInputForValue')
            ->with(FacetKey::TYPE, 'attachment')
            ->andReturn($facetInputD = \Mockery::mock(StringValuesFacetInput::class));

        $searchParameters
            ->expects('withFacetInput')
            ->with(FacetKey::TYPE, $facetInputD)
            ->andReturn(\Mockery::mock(SearchParameters::class));

        $result = $this->mapper->map(
            ElasticField::TOPLEVEL_TYPE->value,
            [
                new TypeArray([
                    'key' => ElasticDocumentType::WOO_DECISION->value,
                    'doc_count' => 9,
                    ElasticField::SUBLEVEL_TYPE->value => [
                        'buckets' => [
                            [
                                'key' => ElasticDocumentType::WOO_DECISION_DOCUMENT->value,
                                'doc_count' => 5,
                            ],
                            [
                                'key' => ElasticDocumentType::ATTACHMENT->value,
                                'doc_count' => 1,
                            ],
                        ],
                    ],
                    'publication' => [
                        'doc_count' => 3,
                    ],
                ]),
            ],
            $searchParameters,
        );

        self::assertCount(1, $result->getEntries());
        self::assertEquals('dossier', $result->getEntries()[0]->getKey());

        $subEntries = $result->getEntries()[0]->getSubEntries();
        self::assertCount(3, $subEntries);
        self::assertEquals('publication', $subEntries[0]->getKey());
        self::assertEquals(3, $subEntries[0]->getCount());
        self::assertEquals('document', $subEntries[1]->getKey());
        self::assertEquals(5, $subEntries[1]->getCount());
        self::assertEquals('attachment', $subEntries[2]->getKey());
        self::assertEquals(1, $subEntries[2]->getCount());
    }
}
