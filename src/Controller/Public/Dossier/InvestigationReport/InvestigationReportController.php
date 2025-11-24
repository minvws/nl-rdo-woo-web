<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\InvestigationReport;

use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel\InvestigationReportViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class InvestigationReportController extends AbstractController
{
    public function __construct(
        private readonly InvestigationReportViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/onderzoeksrapport/{prefix}/{dossierId}', name: 'app_investigationreport_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        InvestigationReportMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(ucfirst($investigationReport->getTitle() ?? ''));

        return $this->render('public/dossier/investigation-report/details.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
            'document' => $this->mainDocumentViewFactory->make($investigationReport, $document),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{prefix}/{dossierId}/document',
        name: 'app_investigationreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        InvestigationReportMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('public/dossier/investigation-report/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_investigationreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        InvestigationReportAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('public/dossier/investigation-report/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
