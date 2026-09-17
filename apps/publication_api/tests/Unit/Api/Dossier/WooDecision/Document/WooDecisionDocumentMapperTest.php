<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;

final class WooDecisionDocumentMapperTest extends UnitTestCase
{
    public function testCreateDocumentSetsSourceTypeFromDto(): void
    {
        $sourceType = SourceType::EMAIL;

        $dto = $this->createDto(fileName: 'test.eml', sourceType: $sourceType);

        $document = WooDecisionDocumentMapper::create($dto);

        $this->assertEquals($sourceType, $document->getFileInfo()->getSourceType());
        self::assertSame('test-doc.123', $document->getDocumentNumber()->toString());
    }

    public function testUpdateDocumentSetsSourceTypeFromDto(): void
    {
        $initialDto = $this->createDto(
            documentId: 'doc.456',
            fileName: 'original.doc',
            sourceType: SourceType::PDF,
            publicationContext: 'original',
        );

        $updateDto = $this->createDto(
            documentId: 'doc.123',
            fileName: 'updated.doc',
            sourceType: SourceType::DOC,
            publicationContext: 'updated',
        );

        $document = WooDecisionDocumentMapper::create($initialDto);
        $this->assertEquals(SourceType::PDF, $document->getFileInfo()->getSourceType());

        $document = WooDecisionDocumentMapper::update($document, $updateDto);

        $this->assertEquals(SourceType::DOC, $document->getFileInfo()->getSourceType());
        $this->assertStringContainsString('doc.123', $document->getDocumentNumber()->toString());
    }

    public function testUpdateKeepsTheFileProperties(): void
    {
        $document = $this->createUploadedDocument();

        WooDecisionDocumentMapper::update($document, $this->createDto(
            fileName: 'renamed.pdf',
            judgement: Judgement::NOT_PUBLIC,
            isSuspended: true,
            documentDate: '2025-06-06',
        ));

        $fileInfo = $document->getFileInfo();
        self::assertSame('renamed.pdf', $fileInfo->getName());
        self::assertTrue($fileInfo->isUploaded());
        self::assertSame('hash-of-original', $fileInfo->getHash());
        self::assertSame('/path/to/original.pdf', $fileInfo->getPath());
        self::assertSame(1234, $fileInfo->getSize());
    }

    private function createUploadedDocument(): Document
    {
        $document = new Document();
        $document->setExternalId(ExternalId::create('ext-123'));
        $document->setDocumentNumber(DocumentNumber::fromString('doc-123'));

        $fileInfo = $document->getFileInfo();
        $fileInfo->setName('original.pdf');
        $fileInfo->setUploaded(true);
        $fileInfo->setHash('hash-of-original');
        $fileInfo->setPath('/path/to/original.pdf');
        $fileInfo->setSize(1234);

        return $document;
    }

    private function createDto(
        string $documentId = 'doc.123',
        string $fileName = 'test.pdf',
        SourceType $sourceType = SourceType::PDF,
        Judgement $judgement = Judgement::PUBLIC,
        bool $isSuspended = false,
        string $documentDate = '2025-01-01',
        string $publicationContext = 'test',
    ): WooDecisionDocumentRequestDto {
        return new WooDecisionDocumentRequestDto(
            inquiryNumbers: [],
            documentDate: PlainDate::create($documentDate),
            documentId: DocumentId::create($documentId),
            externalId: ExternalId::create('ext-123'),
            familyId: 1,
            fileName: FileName::create($fileName),
            grounds: [],
            isSuspended: $isSuspended,
            judgement: $judgement,
            links: [],
            refersTo: [],
            remark: null,
            sourceType: $sourceType,
            threadId: null,
            publicationContext: PublicationContext::fromString($publicationContext),
        );
    }
}
