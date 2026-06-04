<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use Mockery;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentMapper;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Service\ObjectHasher;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;

final class WooDecisionDocumentMapperTest extends UnitTestCase
{
    public function testCreateDocumentSetsSourceTypeFromDto(): void
    {
        $sourceType = SourceType::EMAIL;
        $documentId = 'doc-123';

        $dto = new WooDecisionDocumentRequestDto(
            caseNumbers: [],
            documentDate: PlainDate::create('2025-01-01'),
            documentId: $documentId,
            externalId: ExternalId::create('ext-123'),
            familyId: 1,
            fileName: FileName::create('test.eml'),
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

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('deleteAllFilesForEntity');

        $uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $uploadEntityRepository->expects('removeAllByContextData');

        $wooDecisionDocumentMapper = new WooDecisionDocumentMapper(
            $entityStorageService,
            new ObjectHasher(),
            $uploadEntityRepository,
        );
        $document = $wooDecisionDocumentMapper->create('PREFIX', $dto);

        $this->assertEquals($sourceType, $document->getFileInfo()->getSourceType());
    }

    public function testUpdateDocumentSetsSourceTypeFromDto(): void
    {
        $sourceType = SourceType::DOC;
        $updateDto = new WooDecisionDocumentRequestDto(
            caseNumbers: [],
            documentDate: PlainDate::create('2025-01-01'),
            documentId: 'doc-456',
            externalId: ExternalId::create('ext-456'),
            familyId: 2,
            fileName: FileName::create('updated.doc'),
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
            documentDate: PlainDate::create('2025-01-01'),
            documentId: 'doc-456',
            externalId: ExternalId::create('ext-456'),
            familyId: 2,
            fileName: FileName::create('original.doc'),
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

        $entityStorageService = Mockery::mock(EntityStorageService::class);
        $entityStorageService->expects('deleteAllFilesForEntity')
            ->times(2);

        $uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $uploadEntityRepository->expects('removeAllByContextData')
            ->times(2);

        $wooDecisionDocumentMapper = new WooDecisionDocumentMapper(
            $entityStorageService,
            new ObjectHasher(),
            $uploadEntityRepository,
        );
        $document = $wooDecisionDocumentMapper->create('PREFIX', $initialDto);
        $this->assertEquals(SourceType::PDF, $document->getFileInfo()->getSourceType());

        $document = $wooDecisionDocumentMapper->update($document, $updateDto);

        $this->assertEquals($sourceType, $document->getFileInfo()->getSourceType());
    }
}
