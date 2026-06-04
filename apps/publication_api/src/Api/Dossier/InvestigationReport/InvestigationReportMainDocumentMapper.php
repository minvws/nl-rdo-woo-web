<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\MainDocument\MainDocumentRequestDto;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\FileInfo;
use Webmozart\Assert\Assert;

class InvestigationReportMainDocumentMapper
{
    public static function create(
        InvestigationReport $investigationReport,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        $mainDocument = new InvestigationReportMainDocument(
            $investigationReport,
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
        InvestigationReport $investigationReport,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        $mainDocument = $investigationReport->getMainDocument();
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
