<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\CleanUploads;
use App\Domain\Upload\UploadCleaner;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanUploadsTest extends MockeryTestCase
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
