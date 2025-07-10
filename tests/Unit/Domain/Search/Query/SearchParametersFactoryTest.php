<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query;

use App\Domain\Department\Department;
use App\Domain\Search\Query\Facet\Input\FacetInput;
use App\Domain\Search\Query\Facet\Input\FacetInputCollection;
use App\Domain\Search\Query\Facet\Input\FacetInputFactory;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Search\Model\FacetKey;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class SearchParametersFactoryTest extends MockeryTestCase
{
    private FacetInputFactory&MockInterface $facetInputFactory;
    private SearchParametersFactory $factory;

    public function setUp(): void
    {
        $this->facetInputFactory = \Mockery::mock(FacetInputFactory::class);

        $this->factory = new SearchParametersFactory($this->facetInputFactory);
    }

    public function testCreateDefault(): void
    {
        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);
        $this->facetInputFactory->expects('create')->andReturn($facetInputCollection);

        $parameters = $this->factory->createDefault();

        self::assertSame($parameters->facetInputs, $facetInputCollection);
    }

    public function testCreateForDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->expects('getName')->andReturn('Foo');
        $department->expects('getShortTag')->andReturn('F');

        $newFacetInput = \Mockery::mock(FacetInput::class);
        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);

        $this->facetInputFactory->expects('create')->andReturn($facetInputCollection);
        $this->facetInputFactory->expects('createFacetInput')->with(FacetKey::DEPARTMENT, \Mockery::on(
            static fn (ParameterBag $params) => $params->get(FacetKey::DEPARTMENT->getParamName()) === [0 => 'F|Foo']
        ))->andReturn($newFacetInput);

        $newFacetInputCollection = \Mockery::mock(FacetInputCollection::class);

        $facetInputCollection
            ->expects('withFacetInput')
            ->with(FacetKey::DEPARTMENT, $newFacetInput)
            ->andReturn($newFacetInputCollection);

        $parameters = $this->factory->createForDepartment($department);

        self::assertSame($parameters->facetInputs, $newFacetInputCollection);
    }
}
