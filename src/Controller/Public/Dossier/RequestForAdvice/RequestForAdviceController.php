<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\RequestForAdvice;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\ViewModel\RequestForAdviceViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class RequestForAdviceController extends AbstractController
{
    public function __construct(
        private readonly RequestForAdviceViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly RequestForAdviceMainDocumentRepository $requestForAdviceMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/adviesaanvraag/{documentPrefix}/{dossierNumber}', name: 'app_requestforadvice_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] RequestForAdvice $requestForAdvice,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($requestForAdvice->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($requestForAdvice),
            'attachments' => $this->attachmentViewFactory->makeCollection($requestForAdvice),
        ];

        $document = $this->requestForAdviceMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $requestForAdvice->getDossierNumber(),
        );
        $noticeNotPublic = $requestForAdvice->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($requestForAdvice, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($requestForAdvice);
        }

        return $this->render('public/dossier/request-for-advice/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/adviesaanvraag/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_requestforadvice_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] RequestForAdvice $requestForAdvice,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($requestForAdvice->getType()->getTranslationKey(), 'app_requestforadvice_detail', [
            'documentPrefix' => $requestForAdvice->getDocumentPrefix(),
            'dossierNumber' => $requestForAdvice->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.notice_not_public');

        return $this->render('public/dossier/request-for-advice/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($requestForAdvice),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($requestForAdvice),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/adviesaanvraag/{documentPrefix}/{dossierNumber}/document',
        name: 'app_requestforadvice_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] RequestForAdvice $requestForAdvice,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        RequestForAdviceMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($requestForAdvice->getType()->getTranslationKey(), 'app_requestforadvice_detail', [
            'documentPrefix' => $requestForAdvice->getDocumentPrefix(),
            'dossierNumber' => $requestForAdvice->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/request-for-advice/document.html.twig', [
            'dossier' => $this->viewFactory->make($requestForAdvice),
            'attachments' => $this->attachmentViewFactory->makeCollection($requestForAdvice),
            'document' => $this->mainDocumentViewFactory->make($requestForAdvice, $document),
            'file' => $this->dossierFileViewFactory->make(
                $requestForAdvice,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/adviesaanvraag/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_requestforadvice_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] RequestForAdvice $requestForAdvice,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        RequestForAdviceAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($requestForAdvice->getType()->getTranslationKey(), 'app_requestforadvice_detail', [
            'documentPrefix' => $requestForAdvice->getDocumentPrefix(),
            'dossierNumber' => $requestForAdvice->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/request-for-advice/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($requestForAdvice),
            'attachments' => $this->attachmentViewFactory->makeCollection($requestForAdvice),
            'attachment' => $this->attachmentViewFactory->make($requestForAdvice, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $requestForAdvice,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
