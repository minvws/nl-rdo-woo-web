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

use function mb_ucfirst;

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
    #[Route('/overig/{prefix}/{dossierNumber}', name: 'app_otherpublication_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        Breadcrumbs $breadcrumbs,
        string $prefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(mb_ucfirst((string) $otherPublication->getTitle()));

        $parameters = [
            'dossier' => $this->viewFactory->make($otherPublication),
            'attachments' => $this->attachmentViewFactory->makeCollection($otherPublication),
        ];

        $document = $this->otherPublicationMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $prefix,
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
        '/overig/{prefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_otherpublication_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $otherPublication,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $noticeNotPublicViewModel = $this->noticeNotPublicViewFactory->make($otherPublication);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.other-publication', 'app_otherpublication_detail', [
            'prefix' => $otherPublication->getDocumentPrefix(),
            'dossierNumber' => $otherPublication->getDossierNumber(),
        ]);
        $breadcrumbs->addItem($noticeNotPublicViewModel->title);

        return $this->render('public/dossier/other-publication/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($otherPublication),
            'noticeNotPublic' => $noticeNotPublicViewModel,
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/overig/{prefix}/{dossierNumber}/document',
        name: 'app_otherpublication_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber)')]
        OtherPublicationMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.other-publication', 'app_otherpublication_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem((string) $dossier->getTitle());

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
        '/overig/{prefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_otherpublication_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] OtherPublication $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber, attachmentId)')]
        OtherPublicationAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.other-publication', 'app_otherpublication_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem((string) $dossier->getTitle());

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
