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

use function mb_ucfirst;

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
    #[Route('/beschikking/{prefix}/{dossierNumber}', name: 'app_disposition_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        Breadcrumbs $breadcrumbs,
        string $prefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(mb_ucfirst((string) $dossier->getTitle()));

        $parameters = [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
        ];

        $document = $this->dispositionMainDocumentRepository->findForDossierByPrefixAndDossierNumber($prefix, $dossier->getDossierNumber());
        $noticeNotPublic = $dossier->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($dossier, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($dossier);
        }

        return $this->render('public/dossier/disposition/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_disposition_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $noticeNotPublicViewModel = $this->noticeNotPublicViewFactory->make($dossier);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.disposition', 'app_disposition_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem($noticeNotPublicViewModel->title);

        return $this->render('public/dossier/disposition/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'noticeNotPublic' => $noticeNotPublicViewModel,
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierNumber}/document',
        name: 'app_disposition_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber)')]
        DispositionMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($dossier, $document);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.disposition', 'app_disposition_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem(mb_ucfirst((string) $dossier->getTitle()));

        return $this->render('public/dossier/disposition/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'attachments' => $this->attachmentViewFactory->makeCollection($dossier),
            'document' => $mainDocumentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/beschikking/{prefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_disposition_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] Disposition $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber, attachmentId)')]
        DispositionAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.disposition', 'app_disposition_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem(mb_ucfirst((string) $dossier->getTitle()));

        return $this->render('public/dossier/disposition/attachment.html.twig', [
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
