<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionMainDocument;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ElasticDocumentType: string implements TranslatableInterface
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
        return match (true) {
            $entity instanceof Covenant => self::COVENANT,
            $entity instanceof CovenantMainDocument => self::COVENANT_MAIN_DOCUMENT,
            $entity instanceof CovenantAttachment => self::ATTACHMENT,
            $entity instanceof WooDecision => self::WOO_DECISION,
            $entity instanceof WooDecisionMainDocument => self::WOO_DECISION_MAIN_DOCUMENT,
            $entity instanceof WooDecisionAttachment => self::ATTACHMENT,
            $entity instanceof Document => self::WOO_DECISION_DOCUMENT,
            $entity instanceof AnnualReport => self::ANNUAL_REPORT,
            $entity instanceof AnnualReportMainDocument => self::ANNUAL_REPORT_MAIN_DOCUMENT,
            $entity instanceof AnnualReportAttachment => self::ATTACHMENT,
            $entity instanceof InvestigationReport => self::INVESTIGATION_REPORT,
            $entity instanceof InvestigationReportMainDocument => self::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            $entity instanceof InvestigationReportAttachment => self::ATTACHMENT,
            $entity instanceof Disposition => self::DISPOSITION,
            $entity instanceof DispositionMainDocument => self::DISPOSITION_MAIN_DOCUMENT,
            $entity instanceof DispositionAttachment => self::ATTACHMENT,
            $entity instanceof ComplaintJudgement => self::COMPLAINT_JUDGEMENT,
            $entity instanceof ComplaintJudgementMainDocument => self::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT,
            default => throw IndexException::noTypeFoundForEntityClass(get_class($entity)),
        };
    }

    public static function fromEntityClass(string $entityClass): self
    {
        return match ($entityClass) {
            Covenant::class => self::COVENANT,
            CovenantMainDocument::class => self::COVENANT_MAIN_DOCUMENT,
            CovenantAttachment::class => self::ATTACHMENT,
            WooDecision::class => self::WOO_DECISION,
            WooDecisionMainDocument::class => self::WOO_DECISION_MAIN_DOCUMENT,
            WooDecisionAttachment::class => self::ATTACHMENT,
            Document::class => self::WOO_DECISION_DOCUMENT,
            AnnualReport::class => self::ANNUAL_REPORT,
            AnnualReportMainDocument::class => self::ANNUAL_REPORT_MAIN_DOCUMENT,
            AnnualReportAttachment::class => self::ATTACHMENT,
            InvestigationReport::class => self::INVESTIGATION_REPORT,
            InvestigationReportMainDocument::class => self::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            InvestigationReportAttachment::class => self::ATTACHMENT,
            Disposition::class => self::DISPOSITION,
            DispositionMainDocument::class => self::DISPOSITION_MAIN_DOCUMENT,
            DispositionAttachment::class => self::ATTACHMENT,
            ComplaintJudgement::class => self::COMPLAINT_JUDGEMENT,
            ComplaintJudgementMainDocument::class => self::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT,
            default => throw IndexException::noTypeFoundForEntityClass($entityClass),
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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $prefix = in_array($this, self::getMainTypes(), true) ? 'public.documents.type.' : 'public.search.type.';

        return $translator->trans($prefix . $this->value, locale: $locale);
    }
}
