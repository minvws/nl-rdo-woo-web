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

use function mb_ucfirst;

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
    #[Route('/onderzoeksrapport/{prefix}/{dossierNumber}', name: 'app_investigationreport_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        Breadcrumbs $breadcrumbs,
        string $prefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem(mb_ucfirst((string) $investigationReport->getTitle()));

        $parameters = [
            'dossier' => $this->viewFactory->make($investigationReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($investigationReport),
        ];

        $document = $this->investigationReportMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $prefix,
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
        '/onderzoeksrapport/{prefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_investigationreport_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $investigationReport,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $noticeNotPublicViewModel = $this->noticeNotPublicViewFactory->make($investigationReport);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $investigationReport->getDocumentPrefix(),
            'dossierNumber' => $investigationReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem($noticeNotPublicViewModel->title);

        return $this->render('public/dossier/investigation-report/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($investigationReport),
            'noticeNotPublic' => $noticeNotPublicViewModel,
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/onderzoeksrapport/{prefix}/{dossierNumber}/document',
        name: 'app_investigationreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber)')]
        InvestigationReportMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem((string) $dossier->getTitle());

        return $this->render('public/dossier/investigation-report/document.html.twig', [
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
        '/onderzoeksrapport/{prefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_investigationreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] InvestigationReport $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(prefix, dossierNumber, attachmentId)')]
        InvestigationReportAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $attachmentViewModel = $this->attachmentViewFactory->make($dossier, $attachment);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.investigation-report', 'app_investigationreport_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
        ]);
        $breadcrumbs->addItem((string) $dossier->getTitle());

        return $this->render('public/dossier/investigation-report/attachment.html.twig', [
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
