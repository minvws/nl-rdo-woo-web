<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Shared\Command\Cron\CleanDocumentFileSets;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetRemover;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanDocumentFileSetsTest extends UnitTestCase
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
