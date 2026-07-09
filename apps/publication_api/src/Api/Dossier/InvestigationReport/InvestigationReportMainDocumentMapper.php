<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Webmozart\Assert\Assert;

class InvestigationReportMainDocumentMapper
{
    public static function create(
        InvestigationReport $investigationReport,
        InvestigationReportMainDocumentRequestDto $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        $mainDocument = new InvestigationReportMainDocument(
            $investigationReport,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);

        return $mainDocument;
    }

    public static function update(
        InvestigationReport $investigationReport,
        InvestigationReportMainDocumentRequestDto $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        $mainDocument = $investigationReport->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds($mainDocumentRequestDto->grounds);
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
