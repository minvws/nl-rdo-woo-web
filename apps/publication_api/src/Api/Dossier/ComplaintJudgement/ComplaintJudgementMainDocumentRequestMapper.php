<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

class ComplaintJudgementMainDocumentRequestMapper
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
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));

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
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));
        $mainDocument->setLanguage($mainDocumentRequestDto->language);

        return $mainDocument;
    }
}
