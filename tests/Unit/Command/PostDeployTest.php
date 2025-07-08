<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PostDeploy;
use App\Domain\Content\Page\ContentPageService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PostDeployTest extends MockeryTestCase
{
    public function testDocumentFileSetRemoverIsCalled(): void
    {
        $contentPageService = \Mockery::mock(ContentPageService::class);
        $contentPageService->expects('createMissingPages');

        $command = new PostDeploy($contentPageService);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
