<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\Disposition;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\Disposition\ViewModel\DispositionViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class DispositionController extends AbstractController
{
    public function __construct(
        private readonly DispositionViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly DispositionMainDocumentRepository $dispositionMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/beschikking/{documentPrefix}/{dossierNumber}', name: 'app_disposition_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $disposition,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($disposition->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($disposition),
            'attachments' => $this->attachmentViewFactory->makeCollection($disposition),
        ];

        $document = $this->dispositionMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $disposition->getDossierNumber(),
        );
        $noticeNotPublic = $disposition->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($disposition, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($disposition);
        }

        return $this->render('public/dossier/disposition/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_disposition_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $disposition,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($disposition->getType()->getTranslationKey(), 'app_disposition_detail', [
            'documentPrefix' => $disposition->getDocumentPrefix(),
            'dossierNumber' => $disposition->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.dossiers.notice_not_public');

        return $this->render('public/dossier/disposition/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($disposition),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($disposition),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{documentPrefix}/{dossierNumber}/document',
        name: 'app_disposition_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $disposition,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        DispositionMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($disposition->getType()->getTranslationKey(), 'app_disposition_detail', [
            'documentPrefix' => $disposition->getDocumentPrefix(),
            'dossierNumber' => $disposition->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/disposition/document.html.twig', [
            'dossier' => $this->viewFactory->make($disposition),
            'attachments' => $this->attachmentViewFactory->makeCollection($disposition),
            'document' => $this->mainDocumentViewFactory->make($disposition, $document),
            'file' => $this->dossierFileViewFactory->make(
                $disposition,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_disposition_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $disposition,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        DispositionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($disposition->getType()->getTranslationKey(), 'app_disposition_detail', [
            'documentPrefix' => $disposition->getDocumentPrefix(),
            'dossierNumber' => $disposition->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/disposition/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($disposition),
            'attachments' => $this->attachmentViewFactory->makeCollection($disposition),
            'attachment' => $this->attachmentViewFactory->make($disposition, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $disposition,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
