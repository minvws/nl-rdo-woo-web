<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Carbon\CarbonImmutable;
use Mockery;
use Shared\Command\Cron\CleanWorkerStats;
use Shared\Service\Stats\WorkerStatsRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CleanWorkerStatsTest extends UnitTestCase
{
    public function testRepositoryIsCalled(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::now());

        $repo = Mockery::mock(WorkerStatsRepository::class);
        $repo->expects('removeOldEntries')
            ->with(Mockery::on(
                static function (CarbonImmutable $argument): bool {
                    return $argument == CarbonImmutable::create('-1 week');
                }
            ));

        $command = new CleanWorkerStats($repo);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Done!', $output);
    }
}
