<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\Disposition;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;
use App\Domain\Publication\Dossier\Type\Disposition\ViewModel\DispositionViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DispositionController extends AbstractController
{
    public function __construct(
        private readonly DispositionViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/beschikking/{prefix}/{dossierId}', name: 'app_disposition_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        DispositionDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('disposition/details.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierId}/document',
        name: 'app_disposition_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        DispositionDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($dossier, $document);

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.disposition', 'app_disposition_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('disposition/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $mainDocumentViewModel,
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierId}/document/download',
        name: 'app_disposition_document_download',
        methods: ['GET'],
    )]
    public function documentDownload(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        DispositionDocument $document,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_disposition_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        DispositionAttachment $attachment,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        DispositionDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.disposition', 'app_disposition_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('disposition/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'attachment' => $attachmentViewModel,
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierId}/bijlage/{attachmentId}/download',
        name: 'app_disposition_attachment_download',
        methods: ['GET'],
    )]
    public function attachmentDownload(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        DispositionAttachment $attachment,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($attachment);
    }
}
