<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DocumentInquiryNumbersTest extends UnitTestCase
{
    public function testFromArrayWithEmptyArray(): void
    {
        $documentInquiryNumbers = DocumentInquiryNumbers::fromArray([]);

        self::assertTrue($documentInquiryNumbers->isDocumentNotFound());
        self::assertEquals(null, $documentInquiryNumbers->documentId);
        self::assertEquals([], $documentInquiryNumbers->inquiryNumbers->values);
    }

    public function testFromArrayWithoutInquiryNumbers(): void
    {
        $documentInquiryNumbers = DocumentInquiryNumbers::fromArray([
            [
                'id' => $documentId = Uuid::v6(),
                'inquiryNumber' => null,
            ],
        ]);

        self::assertFalse($documentInquiryNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentInquiryNumbers->documentId);
        self::assertEquals([], $documentInquiryNumbers->inquiryNumbers->values);
    }

    public function testFromArrayWithInquiryNumbers(): void
    {
        $documentId = Uuid::v6();
        $documentInquiryNumbers = DocumentInquiryNumbers::fromArray([
            [
                'id' => $documentId,
                'inquiryNumber' => $inquiryNumber1 = '123-foo',
            ],
            [
                'id' => $documentId,
                'inquiryNumber' => $inquiryNumber2 = '456-bar',
            ],
        ]);

        self::assertFalse($documentInquiryNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentInquiryNumbers->documentId);
        self::assertEquals([$inquiryNumber1, $inquiryNumber2], $documentInquiryNumbers->inquiryNumbers->values);
    }

    public function testFromDocumentEntity(): void
    {
        $inquiryA = Mockery::mock(Inquiry::class);
        $inquiryA->expects('getInquiryNumber')->andReturn($inquiryNumber1 = '123-foo');

        $inquiryB = Mockery::mock(Inquiry::class);
        $inquiryB->expects('getInquiryNumber')->andReturn($inquiryNumber2 = '456-bar');

        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId = Uuid::v6());
        $document->expects('getInquiries')->andReturn(new ArrayCollection([$inquiryA, $inquiryB]));

        $documentInquiryNumbers = DocumentInquiryNumbers::fromDocumentEntity($document);

        self::assertFalse($documentInquiryNumbers->isDocumentNotFound());
        self::assertEquals($documentId, $documentInquiryNumbers->documentId);
        self::assertEquals([$inquiryNumber1, $inquiryNumber2], $documentInquiryNumbers->inquiryNumbers->values);
    }
}
