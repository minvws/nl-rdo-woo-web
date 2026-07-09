<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

class WooDecisionMainDocumentRequestMapper
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

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionMainDocumentRequestDto $mainDocumentRequestDto,
    ): WooDecisionMainDocument {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
