<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Result;

use App\Citation;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\AggregationBucketEntry;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Result\AggregationMapper;
use App\SourceType;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AggregationMapperTest extends MockeryTestCase
{
    private TranslatorInterface|MockInterface $translator;
    private AggregationMapper $mapper;

    public function setUp(): void
    {
        $this->translator = \Mockery::mock(TranslatorInterface::class);

        $this->mapper = new AggregationMapper($this->translator);
    }

    public function testMapGrounds(): void
    {
        $result = $this->mapper->map(
            FacetKey::GROUNDS->value,
            [
                new TypeArray(['key' => Citation::DUBBEL, 'doc_count' => 123]),
                new TypeArray(['key' => '5.1.1a', 'doc_count' => 456]),
                new TypeArray(['key' => 'foo.bar', 'doc_count' => 789]),
            ]
        );

        // Citation 'dubbel' should be skipped, citation '5.1.1a' translated, unknown citations outputted as-is.
        $expectedEntries = [
            new AggregationBucketEntry(
                '5.1.1a',
                456,
                '5.1.1a Eenheid van de Kroon',
            ),
            new AggregationBucketEntry(
                'foo.bar',
                789,
                'foo.bar',
            ),
        ];

        $this->assertEquals(
            new Aggregation(FacetKey::GROUNDS->value, $expectedEntries),
            $result
        );
    }

    public function testMapSource(): void
    {
        $this->translator->expects('trans')->with(SourceType::SOURCE_EMAIL)->andReturn('foo-bar');

        $result = $this->mapper->map(
            FacetKey::SOURCE->value,
            [
                new TypeArray(['key' => SourceType::SOURCE_EMAIL, 'doc_count' => 123]),
            ]
        );

        $expectedEntries = [
            new AggregationBucketEntry(
                SourceType::SOURCE_EMAIL,
                123,
                'foo-bar',
            ),
        ];

        $this->assertEquals(
            new Aggregation(FacetKey::SOURCE->value, $expectedEntries),
            $result
        );
    }

    public function testMapEmptyValueReturnsNone(): void
    {
        $result = $this->mapper->map(
            'dummy',
            [
                new TypeArray(['key' => '', 'doc_count' => 123]),
                new TypeArray(['key' => 'a', 'doc_count' => 456]),
            ]
        );

        $expectedEntries = [
            new AggregationBucketEntry(
                '',
                123,
                'none',
            ),
            new AggregationBucketEntry(
                'a',
                456,
                'a',
            ),
        ];

        $this->assertEquals(
            new Aggregation('dummy', $expectedEntries),
            $result
        );
    }
}
