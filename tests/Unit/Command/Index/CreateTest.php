<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Index;

use Mockery;
use Shared\Command\Index\Create;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Domain\Search\Index\Rollover\MappingService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $name = $this->getFaker()->word();
        $version = $this->getFaker()->randomDigit();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(false);
        $elasticIndexManager->expects('create')
            ->with($name, $version)
            ->andReturn(true);

        $mappingService = Mockery::mock(MappingService::class);

        $application = new Application();
        $application->add(new Create($elasticIndexManager, $mappingService));

        $command = $application->find(Create::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
            'version' => (string) $version, // command always recieves a string when used in cli
        ]);

        self::assertEquals(Create::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithLatestAsVersion(): void
    {
        $name = $this->getFaker()->word();
        $latestMappingVersion = $this->getFaker()->randomDigit();

        $elasticIndexManager = Mockery::mock(ElasticIndexManager::class);
        $elasticIndexManager->expects('exists')
            ->andReturn(false);
        $elasticIndexManager->expects('create')
            ->with($name, $latestMappingVersion)
            ->andReturn(true);

        $mappingService = Mockery::mock(MappingService::class);
        $mappingService->expects('getLatestMappingVersion')
            ->andReturn($latestMappingVersion);

        $application = new Application();
        $application->add(new Create($elasticIndexManager, $mappingService));

        $command = $application->find(Create::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'name' => $name,
            'version' => 'latest',
        ]);

        self::assertEquals(Create::SUCCESS, $commandTester->getStatusCode());
    }
}
