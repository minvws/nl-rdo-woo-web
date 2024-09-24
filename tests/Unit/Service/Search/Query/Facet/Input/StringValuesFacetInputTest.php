<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInput;
use App\Service\Search\Query\Facet\Input\StringValuesFacetInputInterface;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Group('facet')]
#[Group('facetInput')]
final class StringValuesFacetInputTest extends UnitTestCase
{
    protected FacetKey $key;
    protected ParameterBag&MockInterface $bag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = current(FacetKey::cases());
        $this->bag = \Mockery::mock(ParameterBag::class);
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn([])->byDefault();
    }

    public function testItCanBeInitialized(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertInstanceOf(StringValuesFacetInput::class, $input);
        self::assertInstanceOf(StringValuesFacetInputInterface::class, $input);
    }

    public function testIsActiveReturnsTrueWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['one']);

        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertTrue($input->isActive());
    }

    public function testIsActiveReturnsFalseWhenItHasNoValues(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isActive());
    }

    public function testIsNotActiveReturnsFalseWhenItHasValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['one']);

        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertFalse($input->isNotActive());
    }

    public function testIsNotActiveReturnsTrueWhenItHasNoValues(): void
    {
        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertTrue($input->isNotActive());
    }

    public function testGetStringValues(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['one' => 'foo', 'two' => 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame(['foo', 'bar'], $input->getStringValues());
    }

    public function testItThrowsAnExceptionIfNotAllValuesAreAString(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Expected a string. Got: integer'));

        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['one' => 1]);

        StringValuesFacetInput::fromParameterBag($this->key, $this->bag);
    }

    public function testGetRequestParameters(): void
    {
        $this->bag->shouldReceive('all')->with($this->key->getParamName())->once()->andReturn(['one' => 'foo', 'two' => 'bar']);

        $input = StringValuesFacetInput::fromParameterBag($this->key, $this->bag);

        self::assertSame(['foo', 'bar'], $input->getRequestParameters());
    }
}
