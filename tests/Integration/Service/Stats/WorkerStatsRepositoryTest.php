<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Stats;

use Carbon\CarbonImmutable;
use Shared\Service\Stats\WorkerStatsRepository;
use Shared\Tests\Factory\WorkerStatsFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class WorkerStatsRepositoryTest extends SharedWebTestCase
{
    private WorkerStatsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(WorkerStatsRepository::class);
    }

    public function testRemoveOldEntries(): void
    {
        WorkerStatsFactory::createOne([
            'createdAt' => CarbonImmutable::now(),
        ]);

        WorkerStatsFactory::createOne([
            'createdAt' => CarbonImmutable::now()->subDays(2),
        ]);

        WorkerStatsFactory::createOne([
            'createdAt' => CarbonImmutable::now()->subDays(8),
        ]);

        WorkerStatsFactory::createOne([
            'createdAt' => CarbonImmutable::now()->subWeeks(2),
        ]);

        $result = $this->repository->findAll();
        $this->assertCount(4, $result);

        $this->repository->removeOldEntries(
            CarbonImmutable::now()->subWeek(),
        );

        $result = $this->repository->findAll();
        $this->assertCount(2, $result);
    }
}
