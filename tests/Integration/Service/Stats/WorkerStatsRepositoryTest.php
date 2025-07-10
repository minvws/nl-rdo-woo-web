<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Stats;

use App\Service\Stats\WorkerStatsRepository;
use App\Tests\Factory\WorkerStatsFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkerStatsRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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
