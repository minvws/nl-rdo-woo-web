<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\AnnualReport;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Attachment\ViewModel\AttachmentViewFactory;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\ViewModel\AnnualReportViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class AnnualReportController extends AbstractController
{
    public function __construct(
        private readonly AnnualReportViewFactory $viewFactory,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly AnnualReportMainDocumentRepository $annualReportMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/jaarplan-jaarverslag/{documentPrefix}/{dossierNumber}', name: 'app_annualreport_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $annualReport,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($annualReport->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($annualReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($annualReport),
        ];

        $document = $this->annualReportMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $annualReport->getDossierNumber(),
        );
        $noticeNotPublic = $annualReport->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($annualReport, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($annualReport);
        }

        return $this->render('public/dossier/annual-report/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_annualreport_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $annualReport,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($annualReport->getType()->getTranslationKey(), 'app_annualreport_detail', [
            'documentPrefix' => $annualReport->getDocumentPrefix(),
            'dossierNumber' => $annualReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.dossiers.notice_not_public');

        return $this->render('public/dossier/annual-report/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($annualReport),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($annualReport),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{documentPrefix}/{dossierNumber}/document',
        name: 'app_annualreport_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $annualReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        AnnualReportMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($annualReport->getType()->getTranslationKey(), 'app_annualreport_detail', [
            'documentPrefix' => $annualReport->getDocumentPrefix(),
            'dossierNumber' => $annualReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/annual-report/document.html.twig', [
            'dossier' => $this->viewFactory->make($annualReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($annualReport),
            'document' => $this->mainDocumentViewFactory->make($annualReport, $document),
            'file' => $this->dossierFileViewFactory->make(
                $annualReport,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/jaarplan-jaarverslag/{documentPrefix}/{dossierNumber}/bijlage/{attachmentId}',
        name: 'app_annualreport_attachment_detail',
        methods: ['GET'],
    )]
    public function attachmentDetail(
        #[ValueResolver('dossierWithAccessCheck')] AnnualReport $annualReport,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber, attachmentId)')]
        AnnualReportAttachment $attachment,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($annualReport->getType()->getTranslationKey(), 'app_annualreport_detail', [
            'documentPrefix' => $annualReport->getDocumentPrefix(),
            'dossierNumber' => $annualReport->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.attachment');

        return $this->render('public/dossier/annual-report/attachment.html.twig', [
            'dossier' => $this->viewFactory->make($annualReport),
            'attachments' => $this->attachmentViewFactory->makeCollection($annualReport),
            'attachment' => $this->attachmentViewFactory->make($annualReport, $attachment),
            'file' => $this->dossierFileViewFactory->make(
                $annualReport,
                $attachment,
                DossierFileType::ATTACHMENT,
            ),
        ]);
    }
}
