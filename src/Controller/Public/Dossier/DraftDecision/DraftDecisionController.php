<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\DraftDecision;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\ViewModel\DraftDecisionViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class DraftDecisionController extends AbstractController
{
    public function __construct(
        private readonly DraftDecisionViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/ontwerpbesluit/{documentPrefix}/{dossierNumber}', name: 'app_draftdecision_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] DraftDecision $draftDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        DraftDecisionMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($draftDecision->getType()->getTranslationKey());

        return $this->render('public/dossier/draft-decision/details.html.twig', [
            'dossier' => $this->viewFactory->make($draftDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($draftDecision),
            'document' => $this->mainDocumentViewFactory->make($draftDecision, $document),
            'noticeNotPublic' => null,
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/ontwerpbesluit/{documentPrefix}/{dossierNumber}/document',
        name: 'app_draftdecision_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] DraftDecision $draftDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        DraftDecisionMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($draftDecision->getType()->getTranslationKey(), 'app_draftdecision_detail', [
            'documentPrefix' => $draftDecision->getDocumentPrefix(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/draft-decision/document.html.twig', [
            'dossier' => $this->viewFactory->make($draftDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($draftDecision),
            'document' => $this->mainDocumentViewFactory->make($draftDecision, $document),
            'file' => $this->dossierFileViewFactory->make(
                $draftDecision,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/ontwerpbesluit/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_draftdecision_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] DraftDecision $draftDecision,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        DraftDecisionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($draftDecision->getType()->getTranslationKey(), 'app_draftdecision_detail', [
            'documentPrefix' => $draftDecision->getDocumentPrefix(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/draft-decision/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($draftDecision),
            'attachments' => $this->attachmentViewFactory->makeCollection($draftDecision),
            'attachment' => $this->attachmentViewFactory->make($draftDecision, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $draftDecision,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
