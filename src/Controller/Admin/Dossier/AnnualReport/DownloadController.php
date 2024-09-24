<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DownloadController extends AbstractController
{
    public function __construct(
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Route(
        path: '/balie/dossier/annual-report/{prefix}/{dossierId}/document/download',
        name: 'app_admin_annualreport_document_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'annualReport')]
    public function downloadCovenantDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        AnnualReport $annualReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        AnnualReportDocument $document,
    ): StreamedResponse {
        unset($annualReport); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Route(
        path: '/balie/dossier/annual-report/{prefix}/{dossierId}/attachment/{attachmentId}/download',
        name: 'app_admin_annualreport_attachment_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'annualReport')]
    public function downloadCovenantAttachment(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        AnnualReport $annualReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        AnnualReportAttachment $attachment,
    ): StreamedResponse {
        unset($annualReport); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
