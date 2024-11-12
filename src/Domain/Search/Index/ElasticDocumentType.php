<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionMainDocument;

enum ElasticDocumentType: string
{
    case WOO_DECISION = 'dossier';
    case WOO_DECISION_MAIN_DOCUMENT = 'woo_decision_main_document';
    case WOO_DECISION_DOCUMENT = 'document';

    case COVENANT = 'covenant';
    case COVENANT_MAIN_DOCUMENT = 'covenant_main_document';

    case ANNUAL_REPORT = 'annual_report';
    case ANNUAL_REPORT_MAIN_DOCUMENT = 'annual_report_main_document';

    case INVESTIGATION_REPORT = 'investigation_report';
    case INVESTIGATION_REPORT_MAIN_DOCUMENT = 'investigation_report_main_document';

    case DISPOSITION = 'disposition';
    case DISPOSITION_MAIN_DOCUMENT = 'disposition_main_document';

    case COMPLAINT_JUDGEMENT = 'complaint_judgement';
    case COMPLAINT_JUDGEMENT_MAIN_DOCUMENT = 'complaint_judgement_main_document';

    case ATTACHMENT = 'attachment';

    public static function fromEntity(object $entity): self
    {
        if ($entity instanceof AbstractDossier) {
            return match ($entity->getType()) {
                DossierType::COVENANT => self::COVENANT,
                DossierType::WOO_DECISION => self::WOO_DECISION,
                DossierType::ANNUAL_REPORT => self::ANNUAL_REPORT,
                DossierType::INVESTIGATION_REPORT => self::INVESTIGATION_REPORT,
                DossierType::DISPOSITION => self::DISPOSITION,
                DossierType::COMPLAINT_JUDGEMENT => self::COMPLAINT_JUDGEMENT,
            };
        }

        return match (true) {
            $entity instanceof AbstractAttachment => self::ATTACHMENT,
            $entity instanceof CovenantMainDocument => self::COVENANT_MAIN_DOCUMENT,
            $entity instanceof AnnualReportMainDocument => self::ANNUAL_REPORT_MAIN_DOCUMENT,
            $entity instanceof InvestigationReportMainDocument => self::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            $entity instanceof DispositionMainDocument => self::DISPOSITION_MAIN_DOCUMENT,
            $entity instanceof ComplaintJudgementMainDocument => self::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT,
            $entity instanceof WooDecisionMainDocument => self::WOO_DECISION_MAIN_DOCUMENT,
            default => throw IndexException::noTypeFoundForEntity($entity),
        };
    }

    /**
     * @return self[]
     */
    public static function getMainTypes(): array
    {
        return [
            self::WOO_DECISION,
            self::COVENANT,
            self::ANNUAL_REPORT,
            self::INVESTIGATION_REPORT,
            self::DISPOSITION,
            self::COMPLAINT_JUDGEMENT,
        ];
    }

    /**
     * @return self[]
     */
    public static function getSubTypes(): array
    {
        return [
            self::WOO_DECISION_DOCUMENT,
            self::WOO_DECISION_MAIN_DOCUMENT,
            self::COVENANT_MAIN_DOCUMENT,
            self::ANNUAL_REPORT_MAIN_DOCUMENT,
            self::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            self::DISPOSITION_MAIN_DOCUMENT,
            self::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT,
            self::ATTACHMENT,
        ];
    }

    /**
     * @return self[]
     */
    public static function getMainDocumentTypes(): array
    {
        return [
            self::COVENANT_MAIN_DOCUMENT,
            self::ANNUAL_REPORT_MAIN_DOCUMENT,
            self::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            self::DISPOSITION_MAIN_DOCUMENT,
            self::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT,
            self::WOO_DECISION_MAIN_DOCUMENT,
        ];
    }

    /**
     * @return array<array-key,string>
     */
    public static function getMainTypeValues(): array
    {
        return array_map(
            static fn (ElasticDocumentType $type): string => $type->value,
            self::getMainTypes(),
        );
    }

    /**
     * @return array<array-key,string>
     */
    public static function getSubTypeValues(): array
    {
        return array_map(
            static fn (ElasticDocumentType $type): string => $type->value,
            self::getSubTypes(),
        );
    }
}
