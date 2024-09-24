<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
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
        path: '/balie/dossier/investigation-report/{prefix}/{dossierId}/document/download',
        name: 'app_admin_investigationreport_document_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadDocument(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        InvestigationReportDocument $document,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Route(
        path: '/balie/dossier/investigation-report/{prefix}/{dossierId}/attachment/{attachmentId}/download',
        name: 'app_admin_investigationreport_attachment_download',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function downloadAttachment(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        InvestigationReportAttachment $attachment,
    ): StreamedResponse {
        unset($dossier); // Only used for isGranted check

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
