<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Steps\DocumentsStepDefinition;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentsStepDefinitionTest extends MockeryTestCase
{
    public function testIsCompletedReturnsTrueWhenNoInventoryAndDocumentsAreNeeded(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnFalse();

        $step = new DocumentsStepDefinition();

        self::assertTrue($step->isCompleted($dossier));
    }

    public function testIsCompletedReturnsTrueWhenUploadsAreComplete(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnTrue();
        $dossier->shouldReceive('getRawInventory->getFileInfo->isUploaded')->andReturnTrue();
        $dossier->shouldReceive('getUploadStatus->isComplete')->andReturnTrue();

        $step = new DocumentsStepDefinition();

        self::assertTrue($step->isCompleted($dossier));
    }

    public function testIsCompletedReturnsFalseWhenRequiredUploadsAreIncomplete(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnTrue();
        $dossier->shouldReceive('getRawInventory->getFileInfo->isUploaded')->andReturnTrue();
        $dossier->shouldReceive('getUploadStatus->isComplete')->andReturnFalse();

        $step = new DocumentsStepDefinition();

        self::assertFalse($step->isCompleted($dossier));
    }
}
