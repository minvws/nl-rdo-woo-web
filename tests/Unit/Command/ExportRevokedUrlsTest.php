<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery\MockInterface;
use Shared\Command\ExportRevokedUrls;
use Shared\Service\RevokedUrlService;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ExportRevokedUrlsTest extends UnitTestCase
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
