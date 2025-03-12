<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\OtherPublication;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use App\Domain\Publication\Dossier\Type\OtherPublication\ViewModel\OtherPublicationViewFactory;
use App\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class OtherPublicationController extends AbstractController
{
    public function __construct(
        private readonly OtherPublicationViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/overig/{prefix}/{dossierId}', name: 'app_otherpublication_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        OtherPublicationMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(ucfirst($otherPublication->getTitle() ?? ''));

        return $this->render('public/dossier/other-publication/details.html.twig', [
            'dossier' => $this->viewFactory->make($otherPublication),
            'attachments' => $this->attachmentViewFactory->makeCollection($otherPublication),
            'document' => $this->mainDocumentViewFactory->make($otherPublication, $document),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/overig/{prefix}/{dossierId}/document',
        name: 'app_otherpublication_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        OtherPublicationMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.other-publication', 'app_otherpublication_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('public/dossier/other-publication/document.html.twig', [
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
        '/overig/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_otherpublication_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        OtherPublicationAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.other-publication', 'app_otherpublication_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem($dossier->getTitle() ?? '');

        return $this->render('public/dossier/other-publication/attachment.html.twig', [
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
