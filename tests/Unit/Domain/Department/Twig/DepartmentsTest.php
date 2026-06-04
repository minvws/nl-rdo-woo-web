<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department\Twig;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Department\Twig\Departments;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Webmozart\Assert\Assert;

final class DepartmentsTest extends UnitTestCase
{
    private CacheInterface&MockInterface $cache;
    private DepartmentRepository&MockInterface $repository;
    private ItemInterface&MockInterface $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(DepartmentRepository::class);
        $this->item = Mockery::mock(ItemInterface::class);
        $this->cache = Mockery::mock(CacheInterface::class);
    }

    public function testHasAny(): void
    {
        $this->repository->expects('countPublicDepartments')->andReturn(1);

        $this->item->expects('expiresAfter')->with(Mockery::type('integer'))->andReturn($this->item);

        $this->cache
            ->expects('get')
            ->with(
                'DEPARTMENTS_HAS_ANY',
                Mockery::on(function (callable $callback): bool {
                    $result = $callback($this->item);
                    Assert::boolean($result);

                    return $result;
                }),
            )
            ->andReturn(true);

        $result = new Departments($this->repository, $this->cache)->hasAny();

        $this->assertTrue($result);
    }
}
