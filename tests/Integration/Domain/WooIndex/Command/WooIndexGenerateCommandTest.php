<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex\Command;

use App\Domain\WooIndex\WooIndexFileManager;
use App\Domain\WooIndex\WooIndexNamer;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexWooDecisionStory;
use org\bovigo\vfs\vfsStream;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Attribute\WithStory;

final class WooIndexGenerateCommandTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private Application $applicaton;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        vfsStream::setup();

        $this->applicaton = new Application(self::$kernel);
        $this->applicaton->setAutoExit(false);

        $wooIndexNamer = \Mockery::mock(WooIndexNamer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $wooIndexNamer->shouldReceive('getRandomRunIdSuffix')->andReturn('random-string');
        self::getContainer()->set(WooIndexNamer::class, $wooIndexNamer);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testExecute(): void
    {
        $this->setTestNow('2025-01-01 00:00:00.123456');

        $command = $this->applicaton->find('woo-index:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['interactive' => false]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully published new sitemap to', $output);
        $this->assertStringContainsString('vfs://root/var/www/html/public/sitemap/', $output);
    }

    public function testExecuteWhenPublishingFails(): void
    {
        $fileManager = \Mockery::mock(WooIndexFileManager::class, ['wooIndexDir' => 'woo-index/dir'])->makePartial();
        $fileManager->shouldReceive('publish')->once()->andReturnFalse();

        self::getContainer()->set(WooIndexFileManager::class, $fileManager);

        $command = $this->applicaton->find('woo-index:generate');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Failed publishing the sitemap', $output);
    }
}
