<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query\Facet\Input;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Search\Query\Facet\Definition\SourceFacet;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use Shared\Service\Search\Model\FacetKey;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

use function current;

#[Group('facet')]
#[Group('facetInput')]
final class StringValuesFacetInputTest extends UnitTestCase
{
    protected FacetKey $key;
    protected ParameterBag&MockInterface $bag;
    protected SourceFacet $facet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = current(FacetKey::cases());
        $this->facet = new SourceFacet();
        $this->bag = Mockery::mock(ParameterBag::class);
        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn([])->byDefault();
    }

    public function testItCanBeInitialized(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertInstanceOf(StringValuesFacetInput::class, $input);
        self::assertInstanceOf(StringValuesFacetInputInterface::class, $input);
    }

    public function testIsActiveReturnsTrueWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn(['one']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertTrue($input->isActive());
    }

    public function testIsActiveReturnsFalseWhenItHasNoValues(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertFalse($input->isActive());
    }

    public function testIsNotActiveReturnsFalseWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn(['one']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertFalse($input->isNotActive());
    }

    public function testIsNotActiveReturnsTrueWhenItHasNoValues(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertTrue($input->isNotActive());
    }

    public function testGetStringValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn(['one' => 'foo', 'two' => 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertSame(['foo', 'bar'], $input->getStringValues());
    }

    public function testItThrowsAnExceptionIfNotAllValuesAreAString(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Expected a string. Got: integer'));

        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn(['one' => 1]);

        StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);
    }

    public function testGetRequestParameters(): void
    {
        $this->bag->shouldReceive('all')->with($this->facet->getRequestParameter())->once()->andReturn(['one' => 'foo', 'two' => 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertSame(['foo', 'bar'], $input->getRequestParameters());
    }

    public function testContains(): void
    {
        $this->bag
            ->shouldReceive('all')
            ->with($this->facet->getRequestParameter())
            ->once()
            ->andReturn(['one' => 'foo', 'two' => 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);

        self::assertTrue($input->contains('foo'));
        self::assertFalse($input->contains('baz'));
    }

    public function testWithout(): void
    {
        $this->bag
            ->shouldReceive('all')
            ->with($this->facet->getRequestParameter())
            ->once()
            ->andReturn(['foo', 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->facet, $this->bag);
        $updatedInput = $input->without('', 'foo');

        self::assertEquals(
            ['bar'],
            $updatedInput->getRequestParameters(),
        );
    }
}
