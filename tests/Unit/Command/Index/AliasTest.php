<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Index;

use Mockery;
use Shared\Command\Index\Alias;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AliasTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $name = $this->getFaker()->word();
        $alias = $this->getFaker()->word();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(true);
        $elasticIndexManager->expects('alias')
            ->with($name, $alias)
            ->andReturn(true);

        $application = new Application();
        $application->add(new Alias($elasticIndexManager));

        $command = $application->find(Alias::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
            'alias' => $alias,
        ]);

        self::assertEquals(Alias::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteNonExistingIndex(): void
    {
        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(false);
        $elasticIndexManager->expects('alias')
            ->never();

        $application = new Application();
        $application->add(new Alias($elasticIndexManager));

        $command = $application->find(Alias::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $this->getFaker()->word(),
            'alias' => $this->getFaker()->word(),
        ]);

        self::assertEquals(Alias::FAILURE, $commandTester->getStatusCode());
    }
}
