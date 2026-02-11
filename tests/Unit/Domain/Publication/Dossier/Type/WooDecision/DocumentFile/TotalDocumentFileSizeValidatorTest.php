<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\TotalDocumentFileSizeValidator;
use Shared\Tests\Unit\UnitTestCase;

class TotalDocumentFileSizeValidatorTest extends UnitTestCase
{
    public function testExceedsMaxSizeWithUpdatesAppliedReturnsTrueWhenLimitIsExceeded(): void
    {
        $maxAllowedDocumentsSizeInGib = 50;
        $maxAllowedDocumentsSizeInKib = $maxAllowedDocumentsSizeInGib * 1024 * 1024 * 1024;

        $updateA = Mockery::mock(DocumentFileUpdate::class);
        $updateA->shouldReceive('getDocument->getId->toRfc4122')->andReturn('A');
        $updateA->shouldReceive('getFileInfo->getSize')->andReturn($maxAllowedDocumentsSizeInKib);

        $updateC = Mockery::mock(DocumentFileUpdate::class);
        $updateC->shouldReceive('getDocument->getId->toRfc4122')->andReturn('C');
        $updateC->shouldReceive('getFileInfo->getSize')->andReturn($maxAllowedDocumentsSizeInKib);

        $docA = Mockery::mock(Document::class);
        $docA->shouldReceive('getId->toRfc4122')->andReturn('A');

        $docB = Mockery::mock(Document::class);
        $docB->shouldReceive('getId->toRfc4122')->andReturn('B');
        $docB->shouldReceive('getFileInfo->getSize')->andReturn($maxAllowedDocumentsSizeInKib);

        $docC = Mockery::mock(Document::class);
        $docC->shouldReceive('getId->toRfc4122')->andReturn('C');
        $docC->shouldReceive('getFileInfo->getSize')->andReturn(0);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getUpdates')->andReturn(new ArrayCollection([$updateA, $updateC]));
        $documentFileSet->expects('getDossier->getDocuments')->andReturn(new ArrayCollection([$docA, $docB, $docC]));

        // Update replaces doc A and C, doc B remains: 3 times the limit, so exceeds
        self::assertTrue(
            (new TotalDocumentFileSizeValidator($maxAllowedDocumentsSizeInGib))->exceedsMaxSizeWithUpdatesApplied($documentFileSet),
        );
    }

    public function testExceedsMaxSizeWithUpdatesAppliedReturnsFalseWhenLimitIsNotExceeded(): void
    {
        $maxAllowedDocumentsSizeInGib = 40;
        $maxAllowedDocumentsSizeInKib = $maxAllowedDocumentsSizeInGib * 1024 * 1024 * 1024;

        $updateA = Mockery::mock(DocumentFileUpdate::class);
        $updateA->shouldReceive('getDocument->getId->toRfc4122')->andReturn('A');
        $updateA->shouldReceive('getFileInfo->getSize')->andReturn((int) ($maxAllowedDocumentsSizeInKib / 4));

        $updateC = Mockery::mock(DocumentFileUpdate::class);
        $updateC->shouldReceive('getDocument->getId->toRfc4122')->andReturn('C');
        $updateC->shouldReceive('getFileInfo->getSize')->andReturn((int) ($maxAllowedDocumentsSizeInKib / 4));

        $docA = Mockery::mock(Document::class);
        $docA->shouldReceive('getId->toRfc4122')->andReturn('A');

        $docB = Mockery::mock(Document::class);
        $docB->shouldReceive('getId->toRfc4122')->andReturn('B');
        $docB->shouldReceive('getFileInfo->getSize')->andReturn((int) ($maxAllowedDocumentsSizeInKib / 4));

        $docC = Mockery::mock(Document::class);
        $docC->shouldReceive('getId->toRfc4122')->andReturn('C');
        $docC->shouldReceive('getFileInfo->getSize')->andReturn(0);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet->expects('getUpdates')->andReturn(new ArrayCollection([$updateA, $updateC]));
        $documentFileSet->expects('getDossier->getDocuments')->andReturn(new ArrayCollection([$docA, $docB, $docC]));

        // Update replaces doc A and C, doc B remains: 3/4 times the limit, so not exceeding
        self::assertFalse(
            (new TotalDocumentFileSizeValidator($maxAllowedDocumentsSizeInGib))->exceedsMaxSizeWithUpdatesApplied($documentFileSet),
        );
    }
}
