<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Webmozart\Assert\Assert;

class ComplaintJudgementMainDocumentMapper
{
    public static function create(
        ComplaintJudgement $complaintJudgement,
        ComplaintJudgementMainDocumentRequestDto $mainDocumentRequestDto,
    ): ComplaintJudgementMainDocument {
        $mainDocument = new ComplaintJudgementMainDocument(
            $complaintJudgement,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        ComplaintJudgement $complaintJudgement,
        ComplaintJudgementMainDocumentRequestDto $mainDocumentRequestDto,
    ): ComplaintJudgementMainDocument {
        $mainDocument = $complaintJudgement->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
