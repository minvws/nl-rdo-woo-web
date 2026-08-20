<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Service\ObjectHasher;
use Shared\Service\Storage\EntityStorageService;

readonly class WooDecisionDocumentMapper
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private ObjectHasher $objectHasher,
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    public function create(
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $documentNumber = DocumentNumber::fromPublicationContextAndDossierId(
            $wooDecisionDocumentRequestDto->publicationContext,
            $wooDecisionDocumentRequestDto->documentId,
        );

        $document = new Document();
        $document->setExternalId($wooDecisionDocumentRequestDto->externalId);
        $document->setDocumentNumber($documentNumber->toString());
        $document->getFileInfo()->setName($wooDecisionDocumentRequestDto->fileName->toString());

        return $this->update($document, $wooDecisionDocumentRequestDto);
    }

    public function update(
        Document $document,
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $documentHash = $this->objectHasher->get($document);

        $documentNumber = DocumentNumber::fromPublicationContextAndDossierId(
            $wooDecisionDocumentRequestDto->publicationContext,
            $wooDecisionDocumentRequestDto->documentId,
        );

        $document->setDocumentDate($wooDecisionDocumentRequestDto->documentDate);
        $document->setDocumentId($wooDecisionDocumentRequestDto->documentId);
        $document->setDocumentNumber($documentNumber->toString());
        $document->setFamilyId($wooDecisionDocumentRequestDto->familyId);
        $document->setGrounds($wooDecisionDocumentRequestDto->grounds);
        $document->setJudgement($wooDecisionDocumentRequestDto->judgement);
        $document->setLinks($wooDecisionDocumentRequestDto->links);
        $document->setPublicationContext($wooDecisionDocumentRequestDto->publicationContext);
        $document->setSuspended($wooDecisionDocumentRequestDto->isSuspended);
        $document->setRemark($wooDecisionDocumentRequestDto->remark);
        $document->setThreadId($wooDecisionDocumentRequestDto->threadId);

        $fileInfo = $document->getFileInfo();
        $fileInfo->setName($wooDecisionDocumentRequestDto->fileName->toString());
        $fileInfo->setSourceType($wooDecisionDocumentRequestDto->sourceType);

        if ($this->objectHasher->isNotEqual($document, $documentHash)) {
            $this->entityStorageService->deleteAllFilesForEntity($document);
            $this->uploadEntityRepository->removeAllByContextData('documentId', $document->getId()->toRfc4122());
            $fileInfo->removeFileProperties();
        }

        $document->setFileInfo($fileInfo);

        return $document;
    }
}
