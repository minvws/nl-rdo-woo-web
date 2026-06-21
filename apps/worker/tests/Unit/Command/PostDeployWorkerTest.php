<?php

declare(strict_types=1);

namespace Worker\Tests\Unit\Command;

use Mockery;
use Shared\Domain\Content\Page\ContentPageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Worker\Command\PostDeployWorker;

class PostDeployWorkerTest extends UnitTestCase
{
    public function testCreateMissingPagesIsCalled(): void
    {
        $contentPageService = Mockery::mock(ContentPageService::class);
        $contentPageService->expects('createMissingPages');

        $application = new Application();
        $application->addCommand(new PostDeployWorker($contentPageService));

        $commandTester = new CommandTester($application->find('woopie:post-deploy'));
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
