<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\Covenant;

use App\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\ViewModel\CovenantViewFactory;
use App\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class CovenantController extends AbstractController
{
    public function __construct(
        private readonly CovenantViewFactory $covenantViewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/convenant/{prefix}/{dossierId}', name: 'app_covenant_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        CovenantMainDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('global.covenant');

        return $this->render('public/dossier/covenant/details.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'document' => $this->mainDocumentViewFactory->make($covenant, $covenantDocument),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/convenant/{prefix}/{dossierId}/document',
        name: 'app_covenant_document_detail',
        methods: ['GET'],
    )]
    public function covenantDocumentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        CovenantMainDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($covenant, $covenantDocument);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.covenant', 'app_covenant_detail', [
            'prefix' => $covenant->getDocumentPrefix(),
            'dossierId' => $covenant->getDossierNr(),
        ]);
        $breadcrumbs->addItem($mainDocumentViewModel->name ?? '');

        return $this->render('public/dossier/covenant/document.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'document' => $mainDocumentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $covenant,
                $covenantDocument,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/convenant/{prefix}/{dossierId}/bijlage/{attachmentId}',
        name: 'app_covenant_attachment_detail',
        methods: ['GET'],
    )]
    public function covenantAttachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId, attachmentId)')]
        CovenantAttachment $covenantAttachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $covenantAttachmentView = $this->attachmentViewFactory->make($covenant, $covenantAttachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.covenant', 'app_covenant_detail', [
            'prefix' => $covenant->getDocumentPrefix(),
            'dossierId' => $covenant->getDossierNr(),
        ]);
        $breadcrumbs->addItem($covenantAttachmentView->name ?? '');

        return $this->render('public/dossier/covenant/attachment.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'attachment' => $covenantAttachmentView,
            'file' => $this->dossierFileViewFactory->make(
                $covenant,
                $covenantAttachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
