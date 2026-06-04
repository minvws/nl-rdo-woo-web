<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Stats;

use Carbon\CarbonImmutable;
use Shared\Service\Stats\WorkerStatsRepository;
use Shared\Tests\Factory\WorkerStatsFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class WorkerStatsRepositoryTest extends SharedWebTestCase
{
    private WorkerStatsRepository $workerStatsRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workerStatsRepository = self::fromContainer(WorkerStatsRepository::class);
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

        $result = $this->workerStatsRepository->findAll();
        $this->assertCount(4, $result);

        $this->workerStatsRepository->removeOldEntries(
            CarbonImmutable::now()->subWeek(),
        );

        $result = $this->workerStatsRepository->findAll();
        $this->assertCount(2, $result);
    }
}
