<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\PostDeploy;
use Shared\Domain\Content\Page\ContentPageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class PostDeployTest extends UnitTestCase
{
    public function testDocumentFileSetRemoverIsCalled(): void
    {
        $contentPageService = Mockery::mock(ContentPageService::class);
        $contentPageService->expects('createMissingPages');

        $command = new PostDeploy($contentPageService);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
