<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Mockery;
use Shared\Service\PaginatorFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PaginatorFactoryTest extends UnitTestCase
{
    public function testCreateForQuery(): void
    {
        $key = 'foo';
        $sortField = $this->getFaker()->word();
        $limit = $this->getFaker()->randomNumber();
        $pageNr = $this->getFaker()->randomNumber();

        $query = Mockery::mock(Query::class);

        $paginator = Mockery::mock(PaginatorInterface::class);
        $paginator->expects('paginate')
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

        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack->expects('getCurrentRequest')
            ->andReturn(new Request(['foo_p' => $pageNr]));

        $paginatorFactory = new PaginatorFactory($paginator, $requestStack);
        $paginatorFactory->createForQuery($key, $query, $sortField, $limit);
    }
}
