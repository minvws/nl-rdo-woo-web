<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class WooDecisionMainDocumentMapper
{
    public static function create(
        WooDecision $wooDecision,
        WooDecisionMainDocumentRequestDto $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        $mainDocument = new WooDecisionMainDocument(
            $wooDecision,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);

        return $mainDocument;
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionMainDocumentRequestDto $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = $mainDocument->getFileInfo();
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
