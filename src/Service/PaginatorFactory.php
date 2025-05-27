<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

class PaginatorFactory
{
    private const int DEFAULT_LIMIT = 100;

    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return PaginationInterface<int,mixed>
     */
    public function createForQuery(
        string $key,
        Query $query,
        string $defaultSortField,
        int $limit = self::DEFAULT_LIMIT,
    ): PaginationInterface {
        $pageParamName = $key . '_p';
        $sortFieldParamName = $key . '_sf';
        $sortDirectionParamName = $key . '_sd';

        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request);

        return $this->paginator->paginate(
            $query,
            $request->query->getInt($pageParamName, 1),
            $limit,
            [
                PaginatorInterface::PAGE_PARAMETER_NAME => $pageParamName,
                PaginatorInterface::SORT_FIELD_PARAMETER_NAME => $sortFieldParamName,
                PaginatorInterface::SORT_DIRECTION_PARAMETER_NAME => $sortDirectionParamName,
                PaginatorInterface::DEFAULT_SORT_FIELD_NAME => $defaultSortField,
            ],
        );
    }
}
