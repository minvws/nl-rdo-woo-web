<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel\ComplaintJudgementViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Service\DownloadResponseHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class ComplaintJudgementController extends AbstractController
{
    public function __construct(
        private readonly ComplaintJudgementViewFactory $viewFactory,
        private readonly MainDocumentViewFactory $mainDocumentViewFactory,
        private readonly DownloadResponseHelper $downloadHelper,
    ) {
    }

    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[Route('/complaint-judgement/{prefix}/{dossierId}', name: 'app_complaintjudgement_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('complaintjudgement/details.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/complaint-judgement/{prefix}/{dossierId}/document',
        name: 'app_complaintjudgement_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($dossier, $document);

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.complaint-judgement', 'app_complaintjudgement_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('complaintjudgement/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'document' => $mainDocumentViewModel,
        ]);
    }

    #[Cache(maxage: 172800, public: true, mustRevalidate: true)]
    #[Route(
        '/complaint-judgement/{prefix}/{dossierId}/document/download',
        name: 'app_complaintjudgement_document_download',
        methods: ['GET'],
    )]
    public function documentDownload(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementDocument $document,
    ): Response {
        unset($dossier);

        return $this->downloadHelper->getResponseForEntityWithFileInfo($document);
    }
}
