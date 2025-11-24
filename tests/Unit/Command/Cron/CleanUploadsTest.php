<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Shared\Command\Cron\CleanUploads;
use Shared\Domain\Upload\UploadCleaner;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanUploadsTest extends UnitTestCase
{
    public function testDocumentFileSetRemoverIsCalled(): void
    {
        $cleaner = \Mockery::mock(UploadCleaner::class);
        $cleaner->expects('cleanup');

        $command = new CleanUploads($cleaner);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Done cleaning uploads!', $output);
    }
}
