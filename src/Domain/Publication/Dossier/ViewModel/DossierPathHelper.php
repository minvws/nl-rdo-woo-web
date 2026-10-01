<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

use function sprintf;

readonly class DossierPathHelper
{
    public function __construct(
        private RouterInterface $router,
        private string $publicBaseUrl,
    ) {
    }

    public function getDetailsPath(AbstractDossier|DossierReference $dossier): string
    {
        $routeName = match ($dossier->getType()) {
            DossierType::WOO_DECISION => 'app_woodecision_detail',
            DossierType::COVENANT => 'app_covenant_detail',
            DossierType::ANNUAL_REPORT => 'app_annualreport_detail',
            DossierType::INVESTIGATION_REPORT => 'app_investigationreport_detail',
            DossierType::DISPOSITION => 'app_disposition_detail',
            DossierType::COMPLAINT_JUDGEMENT => 'app_complaintjudgement_detail',
            DossierType::OTHER_PUBLICATION => 'app_otherpublication_detail',
            DossierType::ADVICE => 'app_advice_detail',
            DossierType::REQUEST_FOR_ADVICE => 'app_requestforadvice_detail',
            DossierType::DRAFT_DECISION => 'app_draftdecision_detail',
        };

        return $this->router->generate(
            $routeName,
            [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
        );
    }

    public function getAbsoluteDetailsPath(AbstractDossier|DossierReference $dossier): string
    {
        return sprintf('%s%s', $this->publicBaseUrl, $this->getDetailsPath($dossier));
    }

    public function getAbsoluteMainDocumentDetailsPath(AbstractDossier|DossierReference $dossier): string
    {
        return sprintf('%s%s', $this->publicBaseUrl, $this->router->generate(
            sprintf('app_%s_document_detail', $dossier->getType()->getValueForRouteName()),
            [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
        ));
    }

    public function getAbsoluteAttachmentDetailsPath(AbstractDossier|DossierReference $dossier, Uuid $attachmentId): string
    {
        return sprintf('%s%s', $this->publicBaseUrl, $this->router->generate(
            sprintf('app_%s_attachment_detail', $dossier->getType()->getValueForRouteName()),
            [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
                'attachmentId' => $attachmentId,
            ],
        ));
    }
}
