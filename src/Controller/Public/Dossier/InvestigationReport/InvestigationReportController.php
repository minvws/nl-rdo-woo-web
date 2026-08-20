<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\InvestigationReport;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel\InvestigationReportViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class InvestigationReportController extends AbstractController
{
    public function __construct(
        private readonly InvestigationReportViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly InvestigationReportMainDocumentRepository $investigationReportMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/onderzoeksrapport/{documentPrefix}/{dossierNumber}', name: 'app_investigationreport_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($investigationReport->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
        ];

        $document = $this->investigationReportMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $investigationReport->getDossierNumber(),
        );
        $noticeNotPublic = $investigationReport->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($investigationReport, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($investigationReport);
        }

        return $this->render('public/dossier/investigation-report/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_investigationreport_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($investigationReport->getType()->getTranslationKey(), 'app_investigationreport_detail', [
            'documentPrefix' => $investigationReport->getDocumentPrefix(),
            'dossierNumber' => $investigationReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.dossiers.notice_not_public');

        return $this->render('public/dossier/investigation-report/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($investigationReport),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{documentPrefix}/{dossierNumber}/document',
        name: 'app_investigationreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        InvestigationReportMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($investigationReport->getType()->getTranslationKey(), 'app_investigationreport_detail', [
            'documentPrefix' => $investigationReport->getDocumentPrefix(),
            'dossierNumber' => $investigationReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/investigation-report/document.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
            'document' => $this->mainDocumentViewFactory->make($investigationReport, $document),
            'file' => $this->dossierFileViewFactory->make(
                $investigationReport,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_investigationreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        InvestigationReportAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($investigationReport->getType()->getTranslationKey(), 'app_investigationreport_detail', [
            'documentPrefix' => $investigationReport->getDocumentPrefix(),
            'dossierNumber' => $investigationReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/investigation-report/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
            'attachment' => $this->attachmentViewFactory->make($investigationReport, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $investigationReport,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
