<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\ValueObject\DocumentNumber;

class WooDecisionDocumentMapper
{
    public static function create(
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId(
            $wooDecisionDocumentRequestDto->publicationContext,
            $wooDecisionDocumentRequestDto->documentId,
        );

        $document = new Document();
        $document->setExternalId($wooDecisionDocumentRequestDto->externalId);
        $document->setDocumentNumber($documentNumber);
        $document->getFileInfo()->setName($wooDecisionDocumentRequestDto->fileName->toString());

        return self::update($document, $wooDecisionDocumentRequestDto);
    }

    public static function update(
        Document $document,
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId(
            $wooDecisionDocumentRequestDto->publicationContext,
            $wooDecisionDocumentRequestDto->documentId,
        );

        $document->setDocumentDate($wooDecisionDocumentRequestDto->documentDate);
        $document->setDocumentId($wooDecisionDocumentRequestDto->documentId);
        $document->setDocumentNumber($documentNumber);
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

        $document->setFileInfo($fileInfo);

        return $document;
    }
}
