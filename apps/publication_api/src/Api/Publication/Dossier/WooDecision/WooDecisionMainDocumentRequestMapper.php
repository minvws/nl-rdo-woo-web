<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class WooDecisionMainDocumentRequestMapper
{
    public static function create(
        WooDecision $wooDecision,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        $mainDocument = new WooDecisionMainDocument(
            $wooDecision,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        WooDecision $wooDecision,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = $mainDocument->getFileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
