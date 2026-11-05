<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Controller\Public\Dossier\Advice\AdviceController;
use Shared\Controller\Public\Dossier\AnnualReport\AnnualReportController;
use Shared\Controller\Public\Dossier\ComplaintJudgement\ComplaintJudgementController;
use Shared\Controller\Public\Dossier\Covenant\CovenantController;
use Shared\Controller\Public\Dossier\Disposition\DispositionController;
use Shared\Controller\Public\Dossier\DraftDecision\DraftDecisionController;
use Shared\Controller\Public\Dossier\InvestigationReport\InvestigationReportController;
use Shared\Controller\Public\Dossier\OtherPublication\OtherPublicationController;
use Shared\Controller\Public\Dossier\RequestForAdvice\RequestForAdviceController;
use Shared\Controller\Public\Dossier\WooDecision\WooDecisionController;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeException;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

readonly class DossierUrlGenerator
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function attachmentDetailPath(AbstractDossier|DossierReference $dossier, Uuid $attachmentId): string
    {
        return $this->urlGenerator->generate($this->getAttachmentDetailRouteName($dossier->getType()), [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'attachmentId' => $attachmentId,
        ]);
    }

    public function attachmentDetailUrl(AbstractDossier|DossierReference $dossier, Uuid $attachmentId): Url
    {
        return $this->publicUrlGenerator->buildUrlFromPath($this->attachmentDetailPath($dossier, $attachmentId));
    }

    public function detailPath(AbstractDossier|DossierReference $dossier): string
    {
        return $this->urlGenerator->generate($this->getDetailRouteName($dossier->getType()), [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
    }

    public function detailUrl(AbstractDossier|DossierReference $dossier): Url
    {
        return $this->publicUrlGenerator->buildUrlFromPath($this->detailPath($dossier));
    }

    public function documentDetailPath(AbstractDossier|DossierReference $dossier): string
    {
        return $this->urlGenerator->generate($this->getDocumentDetailRouteName($dossier->getType()), [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
    }

    public function documentDetailUrl(AbstractDossier|DossierReference $dossier): Url
    {
        return $this->publicUrlGenerator->buildUrlFromPath($this->documentDetailPath($dossier));
    }

    public function noticeNotPublicDetailPath(AbstractDossier|DossierReference $dossier): string
    {
        return $this->urlGenerator->generate($this->getNoticeNotPublicDetailRouteName($dossier->getType()), [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
    }

    private function getDetailRouteName(DossierType $dossierType): string
    {
        return match ($dossierType) {
            DossierType::ADVICE => AdviceController::ROUTE_NAME_DETAIL,
            DossierType::ANNUAL_REPORT => AnnualReportController::ROUTE_NAME_DETAIL,
            DossierType::COMPLAINT_JUDGEMENT => ComplaintJudgementController::ROUTE_NAME_DETAIL,
            DossierType::COVENANT => CovenantController::ROUTE_NAME_DETAIL,
            DossierType::DISPOSITION => DispositionController::ROUTE_NAME_DETAIL,
            DossierType::DRAFT_DECISION => DraftDecisionController::ROUTE_NAME_DETAIL,
            DossierType::INVESTIGATION_REPORT => InvestigationReportController::ROUTE_NAME_DETAIL,
            DossierType::OTHER_PUBLICATION => OtherPublicationController::ROUTE_NAME_DETAIL,
            DossierType::REQUEST_FOR_ADVICE => RequestForAdviceController::ROUTE_NAME_DETAIL,
            DossierType::WOO_DECISION => WooDecisionController::ROUTE_NAME_DETAIL,
        };
    }

    private function getDocumentDetailRouteName(DossierType $dossierType): string
    {
        return match ($dossierType) {
            DossierType::ADVICE => AdviceController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::ANNUAL_REPORT => AnnualReportController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::COMPLAINT_JUDGEMENT => ComplaintJudgementController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::COVENANT => CovenantController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::DISPOSITION => DispositionController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::DRAFT_DECISION => DraftDecisionController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::INVESTIGATION_REPORT => InvestigationReportController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::OTHER_PUBLICATION => OtherPublicationController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::REQUEST_FOR_ADVICE => RequestForAdviceController::ROUTE_NAME_DOCUMENT_DETAIL,
            DossierType::WOO_DECISION => WooDecisionController::ROUTE_NAME_DOCUMENT_DETAIL,
        };
    }

    private function getAttachmentDetailRouteName(DossierType $dossierType): string
    {
        return match ($dossierType) {
            DossierType::ADVICE => AdviceController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::ANNUAL_REPORT => AnnualReportController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::COVENANT => CovenantController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::DISPOSITION => DispositionController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::DRAFT_DECISION => DraftDecisionController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::INVESTIGATION_REPORT => InvestigationReportController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::OTHER_PUBLICATION => OtherPublicationController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::REQUEST_FOR_ADVICE => RequestForAdviceController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::WOO_DECISION => WooDecisionController::ROUTE_NAME_ATTACHMENT_DETAIL,
            DossierType::COMPLAINT_JUDGEMENT => throw DossierTypeException::forRouteNotAvailable($dossierType),
        };
    }

    private function getNoticeNotPublicDetailRouteName(DossierType $dossierType): string
    {
        return match ($dossierType) {
            DossierType::ADVICE => AdviceController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::ANNUAL_REPORT => AnnualReportController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::COMPLAINT_JUDGEMENT => ComplaintJudgementController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::COVENANT => CovenantController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::DISPOSITION => DispositionController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::INVESTIGATION_REPORT => InvestigationReportController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::OTHER_PUBLICATION => OtherPublicationController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::REQUEST_FOR_ADVICE => RequestForAdviceController::ROUTE_NAME_NOTICE_NOT_PUBLIC_DETAIL,
            DossierType::DRAFT_DECISION,
            DossierType::WOO_DECISION => throw DossierTypeException::forRouteNotAvailable($dossierType),
        };
    }
}
