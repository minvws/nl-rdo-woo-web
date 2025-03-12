<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\CleanDocumentFileSets;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetRemover;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanDocumentFileSetsTest extends MockeryTestCase
{
    public function testDocumentFileSetRemoverIsCalled(): void
    {
        $remover = \Mockery::mock(DocumentFileSetRemover::class);
        $remover->expects('removeAllFinalSets')->andReturn(123);

        $command = new CleanDocumentFileSets($remover);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Cleaned up 123 DocumentFileSets', $output);
    }
}
