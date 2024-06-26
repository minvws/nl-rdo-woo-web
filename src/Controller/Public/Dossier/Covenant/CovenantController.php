<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\Covenant;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\ViewModel\CovenantViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Service\DossierService;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class CovenantController extends AbstractController
{
    public function __construct(
        private readonly DossierService $dossierService,
        private readonly CovenantViewFactory $covenantViewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(public: true, maxage: 3600, mustRevalidate: true)]
    #[Route('/covenant/{prefix}/{dossierId}', name: 'app_covenant_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        CovenantDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('global.covenant');

        if (! $this->dossierService->isViewingAllowed($covenant)) {
            throw $this->createNotFoundException('Covenant not found');
        }

        return $this->render('covenant/details.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'document' => $this->mainDocumentViewFactory->make($covenant, $covenantDocument),
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/covenant/{prefix}/{dossierId}/covenant-document',
        name: 'app_covenant_covenantdocument_detail',
        methods: ['GET'],
    )]
    public function covenantDocumentDetail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        CovenantDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($covenant)) {
            throw $this->createNotFoundException('Covenant not found');
        }

        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($covenant, $covenantDocument);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.covenant', 'app_covenant_detail', [
            'prefix' => $covenant->getDocumentPrefix(),
            'dossierId' => $covenant->getDossierNr(),
        ]);
        $breadcrumbs->addItem($mainDocumentViewModel->name ?? '');

        return $this->render('covenant/document.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'document' => $mainDocumentViewModel,
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/covenant/{prefix}/{dossierId}/covenant-document/download',
        name: 'app_covenant_covenantdocument_download',
        methods: ['GET'],
    )]
    public function covenantDocumentDownload(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        CovenantDocument $covenantDocument,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($covenant)) {
            throw $this->createNotFoundException('Covenant not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($covenantDocument);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/covenant/{prefix}/{dossierId}/covenant-attachment/{attachmentId}',
        name: 'app_covenant_covenantattachment_detail',
        methods: ['GET'],
    )]
    public function covenantAttachmentDetail(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        CovenantAttachment $covenantAttachment,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId)')]
        CovenantDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($covenant)) {
            throw $this->createNotFoundException('Covenant not found');
        }

        $covenantAttachmentView = $this->attachmentViewFactory->make($covenant, $covenantAttachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.covenant', 'app_covenant_detail', [
            'prefix' => $covenant->getDocumentPrefix(),
            'dossierId' => $covenant->getDossierNr(),
        ]);
        $breadcrumbs->addItem($covenantAttachmentView->name ?? '');

        return $this->render('covenant/attachment.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'attachment' => $covenantAttachmentView,
            'document' => $this->mainDocumentViewFactory->make($covenant, $covenantDocument),
        ]);
    }

    #[Cache(public: true, maxage: 172800, mustRevalidate: true)]
    #[Route(
        '/covenant/{prefix}/{dossierId}/covenant-attachment/{attachmentId}/download',
        name: 'app_covenant_covenantattachment_download',
        methods: ['GET'],
    )]
    public function covenantAttachmentDownload(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])]
        Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierPrefixAndNr(prefix, dossierId, attachmentId)')]
        CovenantAttachment $covenantAttachment,
    ): Response {
        if (! $this->dossierService->isViewingAllowed($covenant)) {
            throw $this->createNotFoundException('Covenant not found');
        }

        return $this->downloadHelper->getResponseForEntityWithFileInfo($covenantAttachment);
    }
}
