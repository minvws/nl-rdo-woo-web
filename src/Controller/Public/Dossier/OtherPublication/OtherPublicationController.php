<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\OtherPublication;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\ViewModel\OtherPublicationViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class OtherPublicationController extends AbstractController
{
    public function __construct(
        private readonly OtherPublicationViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly OtherPublicationMainDocumentRepository $otherPublicationMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/overig/{documentPrefix}/{dossierNumber}', name: 'app_otherpublication_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($otherPublication->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($otherPublication),
            'attachments' => $this->attachmentViewFactory->makeCollection($otherPublication),
        ];

        $document = $this->otherPublicationMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $otherPublication->getDossierNumber(),
        );
        $noticeNotPublic = $otherPublication->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($otherPublication, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($otherPublication);
        }

        return $this->render('public/dossier/other-publication/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/overig/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_otherpublication_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($otherPublication->getType()->getTranslationKey(), 'app_otherpublication_detail', [
            'documentPrefix' => $otherPublication->getDocumentPrefix(),
            'dossierNumber' => $otherPublication->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.notice_not_public');

        return $this->render('public/dossier/other-publication/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($otherPublication),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($otherPublication),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/overig/{documentPrefix}/{dossierNumber}/document',
        name: 'app_otherpublication_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        OtherPublicationMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($otherPublication->getType()->getTranslationKey(), 'app_otherpublication_detail', [
            'documentPrefix' => $otherPublication->getDocumentPrefix(),
            'dossierNumber' => $otherPublication->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/other-publication/document.html.twig', [
            'dossier' => $this->viewFactory->make($otherPublication),
            'attachments' => $this->attachmentViewFactory->makeCollection($otherPublication),
            'document' => $this->mainDocumentViewFactory->make($otherPublication, $document),
            'file' => $this->dossierFileViewFactory->make(
                $otherPublication,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/overig/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_otherpublication_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        OtherPublicationAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($otherPublication->getType()->getTranslationKey(), 'app_otherpublication_detail', [
            'documentPrefix' => $otherPublication->getDocumentPrefix(),
            'dossierNumber' => $otherPublication->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/other-publication/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($otherPublication),
            'attachments' => $this->attachmentViewFactory->makeCollection($otherPublication),
            'attachment' => $this->attachmentViewFactory->make($otherPublication, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $otherPublication,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
