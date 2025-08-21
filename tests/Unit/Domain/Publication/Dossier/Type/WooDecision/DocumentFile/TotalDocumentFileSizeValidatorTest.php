<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\TotalDocumentFileSizeValidator;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;

class TotalDocumentFileSizeValidatorTest extends UnitTestCase
{
    private TotalDocumentFileSizeValidator $validator;

    public function setUp(): void
    {
        $this->validator = new TotalDocumentFileSizeValidator();
    }

    public function testExceedsMaxSizeWithUpdatesAppliedReturnsTrueWhenLimitIsExceeded(): void
    {
        $updateA = \Mockery::mock(DocumentFileUpdate::class);
        $updateA->shouldReceive('getDocument->getId->toRfc4122')->andReturn('A');
        $updateA->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE);

        $updateC = \Mockery::mock(DocumentFileUpdate::class);
        $updateC->shouldReceive('getDocument->getId->toRfc4122')->andReturn('C');
        $updateC->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE);

        $docA = \Mockery::mock(Document::class);
        $docA->shouldReceive('getId->toRfc4122')->andReturn('A');

        $docB = \Mockery::mock(Document::class);
        $docB->shouldReceive('getId->toRfc4122')->andReturn('B');
        $docB->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE);

        $docC = \Mockery::mock(Document::class);
        $docC->shouldReceive('getId->toRfc4122')->andReturn('C');
        $docC->shouldReceive('getFileInfo->getSize')->andReturn(0);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getUpdates')->andReturn(new ArrayCollection([$updateA, $updateC]));
        $documentFileSet->expects('getDossier->getDocuments')->andReturn(new ArrayCollection([$docA, $docB, $docC]));

        // Update replaces doc A and C, doc B remains: 3 times the limit, so exceeds
        self::assertTrue(
            $this->validator->exceedsMaxSizeWithUpdatesApplied($documentFileSet),
        );
    }

    public function testExceedsMaxSizeWithUpdatesAppliedReturnsFalseWhenLimitIsNotExceeded(): void
    {
        $updateA = \Mockery::mock(DocumentFileUpdate::class);
        $updateA->shouldReceive('getDocument->getId->toRfc4122')->andReturn('A');
        $updateA->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE / 4);

        $updateC = \Mockery::mock(DocumentFileUpdate::class);
        $updateC->shouldReceive('getDocument->getId->toRfc4122')->andReturn('C');
        $updateC->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE / 4);

        $docA = \Mockery::mock(Document::class);
        $docA->shouldReceive('getId->toRfc4122')->andReturn('A');

        $docB = \Mockery::mock(Document::class);
        $docB->shouldReceive('getId->toRfc4122')->andReturn('B');
        $docB->shouldReceive('getFileInfo->getSize')->andReturn(TotalDocumentFileSizeValidator::MAX_SIZE / 4);

        $docC = \Mockery::mock(Document::class);
        $docC->shouldReceive('getId->toRfc4122')->andReturn('C');
        $docC->shouldReceive('getFileInfo->getSize')->andReturn(0);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getUpdates')->andReturn(new ArrayCollection([$updateA, $updateC]));
        $documentFileSet->expects('getDossier->getDocuments')->andReturn(new ArrayCollection([$docA, $docB, $docC]));

        // Update replaces doc A and C, doc B remains: 3/4 times the limit, so not exceeding
        self::assertFalse(
            $this->validator->exceedsMaxSizeWithUpdatesApplied($documentFileSet),
        );
    }
}
