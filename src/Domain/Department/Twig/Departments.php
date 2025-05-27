<?php

declare(strict_types=1);

namespace App\Domain\Department\Twig;

use App\Repository\DepartmentRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class Departments
{
    private const string DEPARTMENTS_HAS_ANY_CACHE_KEY = 'DEPARTMENTS_HAS_ANY';
    private const int TTL_SHORT = 10;

    public function __construct(
        private DepartmentRepository $repository,
        private CacheInterface $cache,
    ) {
    }

    public function hasAny(): bool
    {
        return $this->cache->get(self::DEPARTMENTS_HAS_ANY_CACHE_KEY, function (ItemInterface $item): bool {
            $item->expiresAfter(self::TTL_SHORT);

            return $this->repository->countPublicDepartments() > 0;
        });
    }
}
