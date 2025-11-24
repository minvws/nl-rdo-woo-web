<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Routing\RouterInterface;

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
        };

        return $this->router->generate(
            $routeName,
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
        );
    }

    public function getAbsoluteDetailsPath(AbstractDossier|DossierReference $dossier): string
    {
        return $this->publicBaseUrl . $this->getDetailsPath($dossier);
    }
}
