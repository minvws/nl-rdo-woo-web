<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\WooDecision\Document;

use PHPUnit\Framework\TestCase;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Publication\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;

final class WooDecisionDocumentMapperTest extends TestCase
{
    public function testCreateDocumentSetsSourceTypeFromDto(): void
    {
        $sourceType = SourceType::EMAIL;
        $dto = new WooDecisionDocumentRequestDto(
            caseNumbers: [],
            date: PlainDate::create('2025-01-01'),
            documentId: 'doc-123',
            externalId: ExternalId::create('ext-123'),
            familyId: 1,
            fileName: 'test.eml',
            grounds: [],
            isSuspended: false,
            judgement: Judgement::PUBLIC,
            links: [],
            matter: 'test',
            refersTo: [],
            remark: null,
            sourceType: $sourceType,
            threadId: null,
        );

        $document = WooDecisionDocumentMapper::create('PREFIX', $dto);

        $this->assertEquals($sourceType, $document->getFileInfo()->getSourceType());
    }

    public function testUpdateDocumentSetsSourceTypeFromDto(): void
    {
        $sourceType = SourceType::DOC;
        $dto = new WooDecisionDocumentRequestDto(
            caseNumbers: [],
            date: PlainDate::create('2025-01-01'),
            documentId: 'doc-456',
            externalId: ExternalId::create('ext-456'),
            familyId: 2,
            fileName: 'updated.doc',
            grounds: [],
            isSuspended: false,
            judgement: Judgement::PUBLIC,
            links: [],
            matter: 'updated',
            refersTo: [],
            remark: null,
            sourceType: $sourceType,
            threadId: null,
        );

        $initialDto = new WooDecisionDocumentRequestDto(
            caseNumbers: [],
            date: PlainDate::create('2025-01-01'),
            documentId: 'doc-456',
            externalId: ExternalId::create('ext-456'),
            familyId: 2,
            fileName: 'original.doc',
            grounds: [],
            isSuspended: false,
            judgement: Judgement::PUBLIC,
            links: [],
            matter: 'original',
            refersTo: [],
            remark: null,
            sourceType: SourceType::PDF,
            threadId: null,
        );

        $document = WooDecisionDocumentMapper::create('PREFIX', $initialDto);
        $this->assertEquals(SourceType::PDF, $document->getFileInfo()->getSourceType());

        WooDecisionDocumentMapper::update($document, $dto);

        $this->assertEquals($sourceType, $document->getFileInfo()->getSourceType());
    }
}
