<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\AnnualReport;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;
use App\Domain\Publication\Dossier\Type\AnnualReport\ViewModel\AnnualReportViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class AnnualReportController extends AbstractController
{
    public function __construct(
        private readonly AnnualReportViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/jaarplan-jaarverslag/{prefix}/{dossierId}', name: 'app_annualreport_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $annualReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        AnnualReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('public.dossiers.annual_report.breadcrumb');

        return $this->render('annualreport/details.html.twig', [
            'dossier' => $this->viewFactory->make($annualReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($annualReport),
            'document' => $this->mainDocumentViewFactory->make($annualReport, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{prefix}/{dossierId}/document',
        name: 'app_annualreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        AnnualReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($dossier, $document);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('public.dossiers.annual_report.breadcrumb', 'app_annualreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($mainDocumentViewModel->name ?? '');

        return $this->render('annualreport/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $mainDocumentViewModel,
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{prefix}/{dossierId}/document/download',
        name: 'app_annualreport_document_download',
        methods: ['GET'],
    )]
    public function documentDownload(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        AnnualReportDocument $document,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_annualreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        AnnualReportAttachment $attachment,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        AnnualReportDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('public.dossiers.annual_report.breadcrumb', 'app_annualreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($attachmentViewModel->name ?? '');

        return $this->render('annualreport/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentViewModel,
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{prefix}/{dossierId}/bijlage/{attachmentId}/download',
        name: 'app_annualreport_attachment_download',
        methods: ['GET'],
    )]
    public function attachmentDownload(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        AnnualReportAttachment $attachment,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
