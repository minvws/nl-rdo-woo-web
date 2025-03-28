<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\Step\StepException;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentsStepDefinition;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DocumentsStepDefinitionTest extends MockeryTestCase
{
    public function testIsCompletedReturnsTrueWhenNoInventoryAndDocumentsAreNeeded(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnFalse();

        $validator = \Mockery::mock(ValidatorInterface::class);

        $step = new DocumentsStepDefinition(
            StepName::DOCUMENTS,
            DossierType::WOO_DECISION,
        );

        self::assertTrue($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedReturnsTrueWhenUploadsAreComplete(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnTrue();
        $dossier->shouldReceive('getProductionReport->getFileInfo->isUploaded')->andReturnTrue();
        $dossier->shouldReceive('getUploadStatus->isComplete')->andReturnTrue();

        $validator = \Mockery::mock(ValidatorInterface::class);

        $step = new DocumentsStepDefinition(
            StepName::DOCUMENTS,
            DossierType::WOO_DECISION,
        );

        self::assertTrue($step->isCompleted($dossier, $validator));
    }

    public function testIsCompletedThrowsExceptionForUnsupportedDossierType(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $validator = \Mockery::mock(ValidatorInterface::class);

        $step = new DocumentsStepDefinition(
            StepName::DOCUMENTS,
            DossierType::WOO_DECISION,
        );

        $this->expectException(StepException::class);
        $step->isCompleted($dossier, $validator);
    }

    public function testIsCompletedReturnsFalseWhenRequiredUploadsAreIncomplete(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturnTrue();
        $dossier->shouldReceive('getProductionReport->getFileInfo->isUploaded')->andReturnTrue();
        $dossier->shouldReceive('getUploadStatus->isComplete')->andReturnFalse();

        $validator = \Mockery::mock(ValidatorInterface::class);

        $step = new DocumentsStepDefinition(
            StepName::DOCUMENTS,
            DossierType::WOO_DECISION,
        );

        self::assertFalse($step->isCompleted($dossier, $validator));
    }
}
