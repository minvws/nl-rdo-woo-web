<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query;

use App\Domain\Search\Query\SearchParametersFactory;
use App\Entity\Department;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class SearchParametersFactoryTest extends MockeryTestCase
{
    private InquirySessionService&MockInterface $inquirySessionService;
    private FacetInputFactory&MockInterface $facetInputFactory;
    private SearchParametersFactory $factory;

    public function setUp(): void
    {
        $this->inquirySessionService = \Mockery::mock(InquirySessionService::class);
        $this->facetInputFactory = \Mockery::mock(FacetInputFactory::class);

        $this->factory = new SearchParametersFactory($this->inquirySessionService, $this->facetInputFactory);
    }

    public function testCreateDefault(): void
    {
        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);
        $this->facetInputFactory->expects('create')->andReturn($facetInputCollection);

        $parameters = $this->factory->createDefault();

        self::assertSame($parameters->facetInputs, $facetInputCollection);
    }

    public function testCreateFromRequestWithInquiriesMissingInSession(): void
    {
        $request = new Request([
            'page' => 9,
            'q' => 'foo',
            'dci' => ['inq-1', 'inq-2'],
        ]);

        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);
        $this->facetInputFactory->expects('fromParameterBag')->with($request->query)->andReturn($facetInputCollection);

        $this->inquirySessionService->expects('getInquiries')->andReturn([]);

        $parameters = $this->factory->createFromRequest($request);

        self::assertSame($parameters->facetInputs, $facetInputCollection);
        self::assertEquals(80, $parameters->offset);
        self::assertEquals('foo', $parameters->query);
        self::assertEquals([], $parameters->documentInquiries);
    }

    public function testCreateFromRequestWithOneInquiryInSession(): void
    {
        $request = new Request([
            'page' => 9,
            'q' => 'foo',
            'dci' => ['inq-1', 'inq-2'],
        ]);

        $facetInputCollection = \Mockery::mock(FacetInputCollection::class);
        $this->facetInputFactory->expects('fromParameterBag')->with($request->query)->andReturn($facetInputCollection);

        $this->inquirySessionService->expects('getInquiries')->andReturn(['inq-2']);

        $parameters = $this->factory->createFromRequest($request);

        self::assertSame($parameters->facetInputs, $facetInputCollection);
        self::assertEquals(80, $parameters->offset);
        self::assertEquals('foo', $parameters->query);
        self::assertEquals(['inq-2'], $parameters->documentInquiries);
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
            static function (ParameterBag $params) {
                return $params->get(FacetKey::DEPARTMENT->getParamName()) === [0 => 'F|Foo'];
            }
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
