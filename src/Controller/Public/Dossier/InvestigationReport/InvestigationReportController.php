<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\InvestigationReport;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;
use App\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel\InvestigationReportViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class InvestigationReportController extends AbstractController
{
    public function __construct(
        private readonly DossierService $dossierService,
        private readonly InvestigationReportViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/investigation-report/{prefix}/{dossierId}', name: 'app_investigationreport_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $investigationReport,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        InvestigationReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(ucfirst($investigationReport->getTitle() ?? ''));

        if (! $this->dossierService->isViewingAllowed($investigationReport)) {
            throw $this->createNotFoundException('Investigation report not found');
        }

        return $this->render('investigationreport/details.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
            'document' => $this->mainDocumentViewFactory->make($investigationReport, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/investigation-report/{prefix}/{dossierId}/document',
        name: 'app_investigationreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        InvestigationReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('investigationreport/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/investigation-report/{prefix}/{dossierId}/document/download',
        name: 'app_investigationreport_document_download',
        methods: ['GET'],
    )]
    public function documentDownload(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        InvestigationReportDocument $document,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/investigation-report/{prefix}/{dossierId}/attachment/{attachmentId}',
        name: 'app_investigationreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        InvestigationReportAttachment $attachment,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        InvestigationReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('investigationreport/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentViewModel,
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/investigation-report/{prefix}/{dossierId}/attachment/{attachmentId}/download',
        name: 'app_investigationreport_attachment_download',
        methods: ['GET'],
    )]
    public function attachmentDownload(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        InvestigationReportAttachment $attachment,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($dossier)) {
            throw $this->createNotFoundException('Dossier not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
