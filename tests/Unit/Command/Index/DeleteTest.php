<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Index;

use Mockery;
use Shared\Command\Index\Delete;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteTest extends UnitTestCase
{
    public function testExecuteWithoutForce(): void
    {
        $name = $this->getFaker()->word();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(true);
        $elasticIndexManager->expects('delete')
            ->never()
            ->with($name);

        $application = new Application();
        $application->add(new Delete($elasticIndexManager));

        $command = $application->find(Delete::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
        ]);

        self::assertEquals(Delete::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithForce(): void
    {
        $name = $this->getFaker()->word();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(true);
        $elasticIndexManager->expects('delete')
            ->with($name)
            ->andReturn(true);

        $application = new Application();
        $application->add(new Delete($elasticIndexManager));

        $command = $application->find(Delete::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
            '--force' => true,
        ]);

        self::assertEquals(Delete::SUCCESS, $commandTester->getStatusCode());
    }
}
