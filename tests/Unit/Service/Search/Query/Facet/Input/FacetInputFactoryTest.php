<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Group('facet')]
#[Group('facetInput')]
final class FacetInputFactoryTest extends UnitTestCase
{
    private ParameterBag&MockInterface $bag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bag = \Mockery::mock(ParameterBag::class);
        $this->bag->shouldReceive('all')->with(\Mockery::type('string'))->andReturn([])->byDefault();
    }

    public function testItCanBeInitialized(): void
    {
        $factory = new FacetInputFactory();

        self::assertInstanceOf(FacetInputFactory::class, $factory);
    }

    public function testCreate(): void
    {
        $result = (new FacetInputFactory())->create();

        self::assertMatchesObjectSnapshot($result);
    }

    public function testFromParameterBag(): void
    {
        $this->bag->shouldReceive('all')->with(FacetKey::JUDGEMENT->getParamName())->once()->andReturn(['one', 'two']);
        $this->bag->shouldReceive('all')->with(FacetKey::GROUNDS->getParamName())->once()->andReturn(['three', 'four']);
        $this->bag->shouldReceive('all')->with(FacetKey::DATE->getParamName())->once()->andReturn(['without_date' => '1']);

        $result = (new FacetInputFactory())->fromParameterBag($this->bag);

        self::assertMatchesObjectSnapshot($result);
    }
}
