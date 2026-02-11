<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class DocumentFileSetTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        self::assertEquals(DocumentFileSetStatus::OPEN_FOR_UPLOADS, $documentFileSet->getStatus());
        self::assertEquals($dossier, $documentFileSet->getDossier());
        self::assertCount(0, $documentFileSet->getUploads());
        self::assertCount(0, $documentFileSet->getUpdates());
    }

    public function testSetAndGetStatus(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);
        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);

        self::assertEquals(DocumentFileSetStatus::COMPLETED, $documentFileSet->getStatus());
    }

    public function testCanConfirmDocumentFileSetThatNeedsConfirmation(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::NEEDS_CONFIRMATION);
        self::assertTrue($documentFileSet->canConfirm());
    }

    public function testCannotConfirmDocumentFileSetThatIsOpenForUploads(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        self::assertFalse($documentFileSet->canConfirm());
    }

    public function testCannotConfirmDocumentFileSetThatIsAlreadyConfirmed(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::CONFIRMED);
        self::assertFalse($documentFileSet->canConfirm());
    }

    public function testCannotConfirmDocumentFileSetThatIsAlreadyCompleted(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::COMPLETED);
        self::assertFalse($documentFileSet->canConfirm());
    }

    public function testCanConfirmDocumentFileSetThatIsProcessingUploadsForAConceptDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::PROCESSING_UPLOADS);
        self::assertTrue($documentFileSet->canConfirm());
    }

    public function testCannotConfirmDocumentFileSetThatIsProcessingUploadsForAPublishedDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $documentFileSet = new DocumentFileSet($dossier);

        $documentFileSet->setStatus(DocumentFileSetStatus::PROCESSING_UPLOADS);
        self::assertFalse($documentFileSet->canConfirm());
    }
}
