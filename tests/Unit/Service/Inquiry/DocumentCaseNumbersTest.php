<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Service\Inquiry\DocumentCaseNumbers;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DocumentCaseNumbersTest extends UnitTestCase
{
    public function testFromArrayWithEmptyArray(): void
    {
        $documentCaseNumbers = DocumentCaseNumbers::fromArray([]);

        self::assertTrue($documentCaseNumbers->isDocumentNotFound());
        self::assertEquals(null, $documentCaseNumbers->documentId);
        self::assertEquals([], $documentCaseNumbers->caseNumbers->values);
    }

    public function testFromArrayWithoutCaseNumbers(): void
    {
        $documentCaseNumbers = DocumentCaseNumbers::fromArray([
            [
                'id' => $documentId = Uuid::v6(),
                'casenr' => null,
            ],
        ]);

        self::assertFalse($documentCaseNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentCaseNumbers->documentId);
        self::assertEquals([], $documentCaseNumbers->caseNumbers->values);
    }

    public function testFromArrayWithCaseNumbers(): void
    {
        $documentId = Uuid::v6();
        $documentCaseNumbers = DocumentCaseNumbers::fromArray([
            [
                'id' => $documentId,
                'casenr' => $caseNr1 = '123-foo',
            ],
            [
                'id' => $documentId,
                'casenr' => $caseNr2 = '456-bar',
            ],
        ]);

        self::assertFalse($documentCaseNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentCaseNumbers->documentId);
        self::assertEquals([$caseNr1, $caseNr2], $documentCaseNumbers->caseNumbers->values);
    }

    public function testFromDocumentEntity(): void
    {
        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->shouldReceive('getCasenr')->andReturn($caseNr1 = '123-foo');

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->shouldReceive('getCasenr')->andReturn($caseNr2 = '456-bar');

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($documentId = Uuid::v6());
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        $documentCaseNumbers = DocumentCaseNumbers::fromDocumentEntity($document);

        self::assertFalse($documentCaseNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentCaseNumbers->documentId);
        self::assertEquals([$caseNr1, $caseNr2], $documentCaseNumbers->caseNumbers->values);
    }
}
