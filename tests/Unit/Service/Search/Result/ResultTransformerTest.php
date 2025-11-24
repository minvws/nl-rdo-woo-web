<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Result;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Knp\Component\Pager\PaginatorInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Query\Facet\Definition\DateFacet;
use Shared\Domain\Search\Query\Facet\Definition\PrefixedDossierNrFacet;
use Shared\Domain\Search\Query\Facet\Input\DateFacetInput;
use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Result\ResultFactory;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Sort\ViewModel\SortItems;
use Shared\Service\Search\Query\Sort\ViewModel\SortItemViewFactory;
use Shared\Service\Search\Result\AggregationMapper;
use Shared\Service\Search\Result\ResultTransformer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ResultTransformerTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private PaginatorInterface&MockInterface $paginator;
    private AggregationMapper&MockInterface $aggregationMapper;
    private ResultFactory&MockInterface $resultFactory;
    private SortItemViewFactory&MockInterface $sortItemViewFactory;
    private ResultTransformer $transformer;

    protected function setUp(): void
    {
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->paginator = \Mockery::mock(PaginatorInterface::class);
        $this->aggregationMapper = \Mockery::mock(AggregationMapper::class);
        $this->resultFactory = \Mockery::mock(ResultFactory::class);
        $this->sortItemViewFactory = \Mockery::mock(SortItemViewFactory::class);

        $this->transformer = new ResultTransformer(
            $this->logger,
            $this->paginator,
            $this->aggregationMapper,
            $this->resultFactory,
            $this->sortItemViewFactory,
        );
    }

    public function testTransform(): void
    {
        $facetInputs = new FacetInputCollection(...[
            FacetKey::PREFIXED_DOSSIER_NR->value => StringValuesFacetInput::fromParameterBag(new PrefixedDossierNrFacet(), new ParameterBag()),
            FacetKey::DATE->value => DateFacetInput::fromParameterBag(new DateFacet(), new ParameterBag()),
        ]);

        $searchParameters = new SearchParameters(
            facetInputs: $facetInputs,
        );

        $response = \Mockery::mock(Elasticsearch::class);

        $json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'response.json');
        if ($json === false) {
            $this->markTestSkipped('Could not load JSON response');
        }

        $data = json_decode($json, true);
        $response->shouldReceive('asArray')->andReturn($data);

        $this->aggregationMapper->shouldReceive('map');
        $this->resultFactory->shouldReceive('map');

        $sortItems = \Mockery::mock(SortItems::class);
        $this->sortItemViewFactory->shouldReceive('make')->with($searchParameters)->andReturn($sortItems);

        $result = $this->transformer->transform(
            [],
            $searchParameters,
            $response,
        );

        self::assertEquals(16, $result->getResultCount());
        self::assertEquals(6, $result->getDossierCount());
        self::assertSame($sortItems, $result->getSortItems());
        self::assertSame($searchParameters, $result->getSearchParameters());
    }
}
