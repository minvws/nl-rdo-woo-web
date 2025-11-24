<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
