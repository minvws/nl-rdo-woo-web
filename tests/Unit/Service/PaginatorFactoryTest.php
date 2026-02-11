<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\PaginatorFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginatorFactoryTest extends UnitTestCase
{
    private PaginatorInterface&MockInterface $paginator;
    private RequestStack&MockInterface $requestStack;
    private PaginatorFactory $factory;

    protected function setUp(): void
    {
        $this->paginator = Mockery::mock(PaginatorInterface::class);
        $this->requestStack = Mockery::mock(RequestStack::class);

        $this->factory = new PaginatorFactory(
            $this->paginator,
            $this->requestStack,
        );

        parent::setUp();
    }

    public function testCreateForQuery(): void
    {
        $key = 'foo';
        $query = Mockery::mock(Query::class);
        $sortField = 'foo.bar';
        $limit = 123;

        $request = Mockery::mock(Request::class);
        $request->query = Mockery::mock(InputBag::class);
        $request->query->expects('getInt')->with('foo_p', 1)->andReturn($pageNr = 4);

        $this->requestStack->expects('getCurrentRequest')->andReturn($request);

        $this->paginator->expects('paginate')
            ->with(
                $query,
                $pageNr,
                $limit,
                [
                    PaginatorInterface::PAGE_PARAMETER_NAME => 'foo_p',
                    PaginatorInterface::SORT_FIELD_PARAMETER_NAME => 'foo_sf',
                    PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => 'foo_sd',
                    PaginatorInterface::DEFAULT_SORT_FIELD_NAME => $sortField,
                ],
            )
            ->andReturn(Mockery::mock(PaginationInterface::class));

        $this->factory->createForQuery($key, $query, $sortField, $limit);
    }
}
