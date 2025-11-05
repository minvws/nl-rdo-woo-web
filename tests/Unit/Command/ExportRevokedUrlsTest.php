<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\ExportRevokedUrls;
use App\Service\RevokedUrlService;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ExportRevokedUrlsTest extends MockeryTestCase
{
    use IterableToGenerator;

    private Command $command;
    private RevokedUrlService&MockInterface $revokedUrlService;

    protected function setUp(): void
    {
        $this->revokedUrlService = \Mockery::mock(RevokedUrlService::class);

        $application = new Application();
        $application->add(
            new ExportRevokedUrls(
                $this->revokedUrlService,
            )
        );

        $this->command = $application->find('woo:export-revoked-urls');
    }

    public function testExecute(): void
    {
        $this->revokedUrlService->expects('getUrls')->andReturn($this->iterableToGenerator(['foo', 'bar']));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        self::assertEquals(0, $commandTester->getStatusCode());

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
    }
}
