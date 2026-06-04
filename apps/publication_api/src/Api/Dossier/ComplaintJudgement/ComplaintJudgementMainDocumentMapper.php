<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class ComplaintJudgementMainDocumentMapper
{
    public static function create(
        ComplaintJudgement $complaintJudgement,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): ComplaintJudgementMainDocument {
        $mainDocument = new ComplaintJudgementMainDocument(
            $complaintJudgement,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        ComplaintJudgement $complaintJudgement,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): ComplaintJudgementMainDocument {
        $mainDocument = $complaintJudgement->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->fileName->toString());

        $mainDocument->setFileInfo($fileInfo);

        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
