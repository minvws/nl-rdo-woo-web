<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Index;

use Mockery;
use Shared\Command\Index\Create;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Tests\ElasticConfigFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $name = $this->getFaker()->word();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(false);
        $elasticIndexManager->expects('create')
            ->with($name)
            ->andReturn(true);
        $application = new Application();
        $application->addCommand(new Create($elasticIndexManager, ElasticConfigFactory::default()));

        $command = $application->find(Create::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
        ]);

        self::assertEquals(Create::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithAliases(): void
    {
        $name = $this->getFaker()->word();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(false);
        $elasticIndexManager->expects('create')
            ->with($name)
            ->andReturn(true);
        $config = ElasticConfigFactory::default();
        $elasticIndexManager->expects('switch')->with($config->readIndex, '*', $name);
        $elasticIndexManager->expects('switch')->with($config->writeIndex, '*', $name);

        $application = new Application();
        $application->addCommand(new Create($elasticIndexManager, $config));

        $command = $application->find(Create::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
            '--read' => true,
            '--write' => true,
        ]);

        self::assertEquals(Create::SUCCESS, $commandTester->getStatusCode());
    }
}
