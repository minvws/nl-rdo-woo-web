<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\Advice\Advice;
use App\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use App\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
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
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

    case OTHER_PUBLICATION = 'other_publication';
    case OTHER_PUBLICATION_MAIN_DOCUMENT = 'other_publication_main_document';

    case ADVICE = 'advice';
    case ADVICE_MAIN_DOCUMENT = 'advice_main_document';

    case REQUEST_FOR_ADVICE = 'request_for_advice';
    case REQUEST_FOR_ADVICE_MAIN_DOCUMENT = 'request_for_advice_main_document';

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
            $entity instanceof OtherPublication => self::OTHER_PUBLICATION,
            $entity instanceof OtherPublicationMainDocument => self::OTHER_PUBLICATION_MAIN_DOCUMENT,
            $entity instanceof OtherPublicationAttachment => self::ATTACHMENT,
            $entity instanceof Advice => self::ADVICE,
            $entity instanceof AdviceMainDocument => self::ADVICE_MAIN_DOCUMENT,
            $entity instanceof AdviceAttachment => self::ATTACHMENT,
            $entity instanceof RequestForAdvice => self::REQUEST_FOR_ADVICE,
            $entity instanceof RequestForAdviceMainDocument => self::REQUEST_FOR_ADVICE_MAIN_DOCUMENT,
            $entity instanceof RequestForAdviceAttachment => self::ATTACHMENT,
            default => throw IndexException::noTypeFoundForEntityClass($entity::class),
        };
    }

    public static function fromDossierType(DossierType $type): self
    {
        return match ($type) {
            DossierType::WOO_DECISION => self::WOO_DECISION,
            DossierType::ADVICE => self::ADVICE,
            DossierType::REQUEST_FOR_ADVICE => self::REQUEST_FOR_ADVICE,
            DossierType::ANNUAL_REPORT => self::ANNUAL_REPORT,
            DossierType::COMPLAINT_JUDGEMENT => self::COMPLAINT_JUDGEMENT,
            DossierType::DISPOSITION => self::DISPOSITION,
            DossierType::COVENANT => self::COVENANT,
            DossierType::OTHER_PUBLICATION => self::OTHER_PUBLICATION,
            DossierType::INVESTIGATION_REPORT => self::INVESTIGATION_REPORT,
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
            OtherPublication::class => self::OTHER_PUBLICATION,
            OtherPublicationMainDocument::class => self::OTHER_PUBLICATION_MAIN_DOCUMENT,
            OtherPublicationAttachment::class => self::ATTACHMENT,
            Advice::class => self::ADVICE,
            AdviceMainDocument::class => self::ADVICE_MAIN_DOCUMENT,
            AdviceAttachment::class => self::ATTACHMENT,
            RequestForAdvice::class => self::REQUEST_FOR_ADVICE,
            RequestForAdviceMainDocument::class => self::REQUEST_FOR_ADVICE_MAIN_DOCUMENT,
            RequestForAdviceAttachment::class => self::ATTACHMENT,
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
            self::OTHER_PUBLICATION,
            self::ADVICE,
            self::REQUEST_FOR_ADVICE,
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
            self::OTHER_PUBLICATION_MAIN_DOCUMENT,
            self::ADVICE_MAIN_DOCUMENT,
            self::REQUEST_FOR_ADVICE_MAIN_DOCUMENT,
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
            self::OTHER_PUBLICATION_MAIN_DOCUMENT,
            self::REQUEST_FOR_ADVICE_MAIN_DOCUMENT,
            self::ADVICE_MAIN_DOCUMENT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function getMainTypeValues(): array
    {
        return self::toValues(
            self::getMainTypes(),
        );
    }

    /**
     * @return list<string>
     */
    public static function getSubTypeValues(): array
    {
        return self::toValues(
            self::getSubTypes(),
        );
    }

    /**
     * @return list<string>
     */
    public static function getMainDocumentTypeValues(): array
    {
        return self::toValues(
            self::getMainDocumentTypes(),
        );
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        $prefix = in_array($this, self::getMainTypes(), true) ? 'public.documents.type.' : 'public.search.type.';

        return $translator->trans($prefix . $this->value, locale: $locale);
    }

    /**
     * @param self[] $types
     *
     * @return list<string>
     */
    private static function toValues(array $types): array
    {
        return array_values(
            array_map(
                static fn (ElasticDocumentType $type): string => $type->value,
                $types,
            ),
        );
    }
}
