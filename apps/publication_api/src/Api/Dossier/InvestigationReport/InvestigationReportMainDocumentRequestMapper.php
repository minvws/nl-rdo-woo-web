<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Service\EnumHelper;
use Webmozart\Assert\Assert;

/**
 * @implements MainDocumentRequestMapperInterface<InvestigationReport,InvestigationReportMainDocumentRequestDto>
 */
class InvestigationReportMainDocumentRequestMapper implements MainDocumentRequestMapperInterface
{
    public function create(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        Assert::isInstanceOf($dossier, InvestigationReport::class);
        Assert::isInstanceOf($mainDocumentRequestDto, InvestigationReportMainDocumentRequestDto::class);

        $mainDocument = new InvestigationReportMainDocument(
            $dossier,
            $mainDocumentRequestDto->formalDate,
            $mainDocumentRequestDto->type,
            $mainDocumentRequestDto->language,
        );

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));

        return $mainDocument;
    }

    public function update(
        AbstractDossier $dossier,
        MainDocumentRequestDtoInterface $mainDocumentRequestDto,
    ): InvestigationReportMainDocument {
        Assert::isInstanceOf($dossier, InvestigationReport::class);
        Assert::isInstanceOf($mainDocumentRequestDto, InvestigationReportMainDocumentRequestDto::class);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocument->getFileInfo()->setName($mainDocumentRequestDto->fileName->toString());
        $mainDocument->setFormalDate($mainDocumentRequestDto->formalDate);
        $mainDocument->setGrounds(EnumHelper::getStringValues($mainDocumentRequestDto->grounds));
        $mainDocument->setLanguage($mainDocumentRequestDto->language);
        $mainDocument->setType($mainDocumentRequestDto->type);

        return $mainDocument;
    }
}
