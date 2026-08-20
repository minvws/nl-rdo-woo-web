<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Webmozart\Assert\Assert;

class DraftDecisionMainDocumentMapper
{
    public static function create(
        DraftDecision $draftDecision,
        DraftDecisionMainDocumentRequestDto $mainDocumentRequestDto,
    ): DraftDecisionMainDocument {
        $mainDocument = new DraftDecisionMainDocument(
            $draftDecision,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());

        return $mainDocument;
    }

    public static function update(
        DraftDecision $draftDecision,
        DraftDecisionMainDocumentRequestDto $mainDocumentRequestDto,
    ): DraftDecisionMainDocument {
        $mainDocument = $draftDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
