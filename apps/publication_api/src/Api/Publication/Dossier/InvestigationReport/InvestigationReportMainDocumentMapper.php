<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use PublicationApi\Api\Publication\MainDocument\MainDocumentRequestDto;
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
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);

        return $mainDocument;
    }

    public static function update(
        InvestigationReport $investigationReport,
        MainDocumentRequestDto $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        $mainDocument = $investigationReport->getMainDocument();
        Assert::notNull($mainDocument);

        $fileInfo = new FileInfo();
        $fileInfo->setName($mainDocumentRequestDto->filename);

        $mainDocument->setFileInfo($fileInfo);
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setInternalReference($mainDocumentRequestDto->internalReference);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
