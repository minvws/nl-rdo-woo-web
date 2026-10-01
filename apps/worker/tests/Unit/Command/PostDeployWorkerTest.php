<?php

declare(strict_types=1);

namespace Worker\Tests\Unit\Command;

use Mockery;
use Shared\Domain\Content\Page\ContentPageService;
use Shared\Domain\PostDeploy\InitialTenantSetup;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Worker\Command\PostDeployWorker;

class PostDeployWorkerTest extends UnitTestCase
{
    public function testInitialTenantSetupIsExecutedOnNewEnvironment(): void
    {
        $contentPageService = Mockery::mock(ContentPageService::class);
        $contentPageService->expects('createMissingPages');

        $initialTenantSetup = Mockery::mock(InitialTenantSetup::class);
        $initialTenantSetup->expects('isNewEnvironment')->andReturnTrue();
        $initialTenantSetup->expects('exec');

        $commandTester = $this->createCommandTester($contentPageService, $initialTenantSetup);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testInitialTenantSetupIsSkippedOnExistingEnvironment(): void
    {
        $contentPageService = Mockery::mock(ContentPageService::class);
        $contentPageService->expects('createMissingPages');

        $initialTenantSetup = Mockery::mock(InitialTenantSetup::class);
        $initialTenantSetup->expects('isNewEnvironment')->andReturnFalse();
        $initialTenantSetup->expects('exec')->never();

        $commandTester = $this->createCommandTester($contentPageService, $initialTenantSetup);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    private function createCommandTester(
        ContentPageService $contentPageService,
        InitialTenantSetup $initialTenantSetup,
    ): CommandTester {
        $application = new Application();
        $application->addCommand(new PostDeployWorker($contentPageService, $initialTenantSetup));

        return new CommandTester($application->find('woopie:post-deploy'));
    }
}
