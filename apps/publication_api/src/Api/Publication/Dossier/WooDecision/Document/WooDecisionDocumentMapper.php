<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Inventory\DocumentNumber;
use Shared\ValueObject\ExternalId;

class WooDecisionDocumentMapper
{
    public static function create(
        string $documentPrefix,
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $documentNr = DocumentNumber::fromString(
            $documentPrefix,
            $wooDecisionDocumentRequestDto->matter,
            $wooDecisionDocumentRequestDto->documentId,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($wooDecisionDocumentRequestDto->fileName);

        $document = new Document();
        $document->setExternalId(ExternalId::create($wooDecisionDocumentRequestDto->externalId));
        $document->setDocumentNr($documentNr->getValue());
        $document->setFileInfo($fileInfo);

        return self::update($document, $wooDecisionDocumentRequestDto);
    }

    public static function update(
        Document $document,
        WooDecisionDocumentRequestDto $wooDecisionDocumentRequestDto,
    ): Document {
        $fileInfo = $document->getFileInfo();
        $fileInfo->setName($wooDecisionDocumentRequestDto->fileName);

        $document->setFileInfo($fileInfo);
        $document->setDocumentDate($wooDecisionDocumentRequestDto->date);
        $document->setDocumentId($wooDecisionDocumentRequestDto->documentId);
        $document->setDocumentNr($document->getDocumentNr());
        $document->setFamilyId($wooDecisionDocumentRequestDto->familyId);
        $document->setGrounds($wooDecisionDocumentRequestDto->grounds);
        $document->setJudgement($wooDecisionDocumentRequestDto->judgement);
        $document->setLinks($wooDecisionDocumentRequestDto->links);
        $document->setPeriod($wooDecisionDocumentRequestDto->period);
        $document->setSuspended($wooDecisionDocumentRequestDto->isSuspended);
        $document->setRemark($wooDecisionDocumentRequestDto->remark);
        $document->setThreadId($wooDecisionDocumentRequestDto->threadId);

        return $document;
    }
}
