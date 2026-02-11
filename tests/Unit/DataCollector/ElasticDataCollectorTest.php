<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\DataCollector;

use Elastic\Elasticsearch\Response\Elasticsearch as ElasticsearchResponse;
use Mockery;
use Shared\DataCollector\ElasticCollector;
use Shared\Tests\Unit\UnitTestCase;

class ElasticDataCollectorTest extends UnitTestCase
{
    private ElasticCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new ElasticCollector();
    }

    public function testCollectorIgnoresCallsWhenDisabled(): void
    {
        $this->collector->disable();

        $response = Mockery::mock(ElasticsearchResponse::class);
        $this->collector->addCall('foo', ['bar'], $response);

        self::assertCount(0, $this->collector->getCalls());
    }

    public function testCollectorCanBeReEnabled(): void
    {
        $this->collector->disable();

        $responseA = Mockery::mock(ElasticsearchResponse::class);
        $this->collector->addCall('foo', ['bar'], $responseA);

        self::assertCount(0, $this->collector->getCalls());

        $this->collector->enable();

        $responseB = Mockery::mock(ElasticsearchResponse::class);
        $responseB->expects('asArray')->andReturn(['foo' => 'bar']);
        $this->collector->addCall('foo', ['bar'], $responseB);

        self::assertCount(1, $this->collector->getCalls());
    }

    public function testCollectorRegistersCallWithoutTypeAsArray(): void
    {
        $response = Mockery::mock(ElasticsearchResponse::class);
        $response->expects('asArray')->andReturn(['foo' => 'bar']);

        $this->collector->addCall('foo', ['bar'], $response);

        $this->assertMatchesYamlSnapshot($this->collector->getCalls());
    }

    public function testCollectorRegistersCallWithBoolType(): void
    {
        $response = Mockery::mock(ElasticsearchResponse::class);
        $response->expects('asBool')->andReturnTrue();

        $this->collector->addCall('foo', ['bar'], $response, 'bool');

        $this->assertMatchesYamlSnapshot($this->collector->getCalls());
    }

    public function testCollectorRegistersCallWithStringType(): void
    {
        $response = Mockery::mock(ElasticsearchResponse::class);
        $response->expects('asString')->andReturn('foo');

        $this->collector->addCall('foo', ['bar'], $response, 'string');

        $this->assertMatchesYamlSnapshot($this->collector->getCalls());
    }

    public function testCollectorRegistersCallWithArrayType(): void
    {
        $response = Mockery::mock(ElasticsearchResponse::class);
        $response->expects('asArray')->andReturn(['foo' => 'bar']);

        $this->collector->addCall('foo', ['bar'], $response, 'array');

        $this->assertMatchesYamlSnapshot($this->collector->getCalls());
    }
}
