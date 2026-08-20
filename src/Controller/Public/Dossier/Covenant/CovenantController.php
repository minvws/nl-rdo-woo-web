<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\Covenant;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\ViewModel\CovenantViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class CovenantController extends AbstractController
{
    public function __construct(
        private readonly CovenantViewFactory $covenantViewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly CovenantMainDocumentRepository $covenantMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/convenant/{documentPrefix}/{dossierNumber}', name: 'app_covenant_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($covenant->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
        ];

        $document = $this->covenantMainDocumentRepository->findForDossierByPrefixAndDossierNumber($documentPrefix, $covenant->getDossierNumber());
        $noticeNotPublic = $covenant->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($covenant, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($covenant);
        }

        return $this->render('public/dossier/covenant/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/convenant/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_covenant_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($covenant->getType()->getTranslationKey(), 'app_covenant_detail', [
            'documentPrefix' => $covenant->getDocumentPrefix(),
            'dossierNumber' => $covenant->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.dossiers.notice_not_public');

        return $this->render('public/dossier/covenant/notice-not-public.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($covenant),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/convenant/{documentPrefix}/{dossierNumber}/document',
        name: 'app_covenant_document_detail',
        methods: ['GET'],
    )]
    public function covenantDocumentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        CovenantMainDocument $covenantDocument,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($covenant, $covenantDocument);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($covenant->getType()->getTranslationKey(), 'app_covenant_detail', [
            'documentPrefix' => $covenant->getDocumentPrefix(),
            'dossierNumber' => $covenant->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

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
        '/convenant/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_covenant_attachment_detail',
        methods: ['GET'],
    )]
    public function covenantAttachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Covenant $covenant,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        CovenantAttachment $covenantAttachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($covenant->getType()->getTranslationKey(), 'app_covenant_detail', [
            'documentPrefix' => $covenant->getDocumentPrefix(),
            'dossierNumber' => $covenant->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/covenant/attachment.html.twig', [
            'dossier' => $this->covenantViewFactory->make($covenant),
            'attachments' => $this->attachmentViewFactory->makeCollection($covenant),
            'attachment' => $this->attachmentViewFactory->make($covenant, $covenantAttachment),
            'file' => $this->dossierFileViewFactory->make(
                $covenant,
                $covenantAttachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
