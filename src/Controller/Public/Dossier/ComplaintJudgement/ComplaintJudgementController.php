<?php

declare(strict_types=1);

namespace Shared\Controller\Public\Dossier\ComplaintJudgement;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use InvalidArgumentException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel\NoticeNotPublicViewFactory;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel\ComplaintJudgementViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class ComplaintJudgementController extends AbstractController
{
    public function __construct(
        private readonly ComplaintJudgementViewFactory $viewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DossierFileViewFactory $dossierFileViewFactory,
        private readonly ComplaintJudgementMainDocumentRepository $complaintJudgementMainDocumentRepository,
        private readonly NoticeNotPublicViewFactory $noticeNotPublicViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/klachtoordeel/{documentPrefix}/{dossierNumber}', name: 'app_complaintjudgement_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $complaintJudgement,
        Breadcrumbs $breadcrumbs,
        string $documentPrefix,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($complaintJudgement->getType()->getTranslationKey());

        $parameters = [
            'dossier' => $this->viewFactory->make($complaintJudgement),
        ];

        $document = $this->complaintJudgementMainDocumentRepository->findForDossierByPrefixAndDossierNumber(
            $documentPrefix,
            $complaintJudgement->getDossierNumber(),
        );
        $noticeNotPublic = $complaintJudgement->getNoticeNotPublic();

        if ($document === null && $noticeNotPublic === null) {
            throw new InvalidArgumentException('either mainDocument or NoticeNotPublic must be set');
        }

        if ($document !== null) {
            $parameters['document'] = $this->mainDocumentViewFactory->make($complaintJudgement, $document);
            $parameters['noticeNotPublic'] = null;
        } else {
            $parameters['document'] = null;
            $parameters['noticeNotPublic'] = $this->noticeNotPublicViewFactory->make($complaintJudgement);
        }

        return $this->render('public/dossier/complaint-judgement/details.html.twig', $parameters);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/klachtoordeel/{documentPrefix}/{dossierNumber}/mededeling-niet-openbaar',
        name: 'app_complaintjudgement_notice_not_public_detail',
        methods: ['GET'],
    )]
    public function noticeNotPublicDetail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $complaintJudgement,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($complaintJudgement->getType()->getTranslationKey(), 'app_complaintjudgement_detail', [
            'documentPrefix' => $complaintJudgement->getDocumentPrefix(),
            'dossierNumber' => $complaintJudgement->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('global.dossiers.notice_not_public');

        return $this->render('public/dossier/complaint-judgement/notice-not-public.html.twig', [
            'dossier' => $this->viewFactory->make($complaintJudgement),
            'noticeNotPublic' => $this->noticeNotPublicViewFactory->make($complaintJudgement),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/klachtoordeel/{documentPrefix}/{dossierNumber}/document',
        name: 'app_complaintjudgement_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $complaintJudgement,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndDossierNumber(documentPrefix, dossierNumber)')]
        ComplaintJudgementMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($complaintJudgement, $document);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem($complaintJudgement->getType()->getTranslationKey(), 'app_complaintjudgement_detail', [
            'documentPrefix' => $complaintJudgement->getDocumentPrefix(),
            'dossierNumber' => $complaintJudgement->getDossierNumber(),
        ]);
        $breadcrumbs->addItem('public.global.main_document');

        return $this->render('public/dossier/complaint-judgement/document.html.twig', [
            'dossier' => $this->viewFactory->make($complaintJudgement),
            'document' => $mainDocumentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $complaintJudgement,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }
}
