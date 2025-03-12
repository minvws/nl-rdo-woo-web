<?php

declare(strict_types=1);

namespace App\Controller\Public\Dossier\ComplaintJudgement;

use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel\ComplaintJudgementViewFactory;
use App\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
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
        private readonly DossierFileViewFactory $dossierFileViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/klachtoordeel/{prefix}/{dossierId}', name: 'app_complaintjudgement_detail', methods: ['GET'])]
    public function detail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('public/dossier/complaint-judgement/details.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'document' => $this->mainDocumentViewFactory->make($dossier, $document),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route(
        '/klachtoordeel/{prefix}/{dossierId}/document',
        name: 'app_complaintjudgement_document_detail',
        methods: ['GET'],
    )]
    public function documentDetail(
        #[ValueResolver('dossierWithAccessCheck')] ComplaintJudgement $dossier,
        #[MapEntity(expr: 'repository.findForDossierByPrefixAndNr(prefix, dossierId)')]
        ComplaintJudgementMainDocument $document,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $mainDocumentViewModel = $this->mainDocumentViewFactory->make($dossier, $document);

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('dossier.type.complaint-judgement', 'app_complaintjudgement_detail', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
        ]);
        $breadcrumbs->addItem(ucfirst($dossier->getTitle() ?? ''));

        return $this->render('public/dossier/complaint-judgement/document.html.twig', [
            'dossier' => $this->viewFactory->make($dossier),
            'document' => $mainDocumentViewModel,
            'file' => $this->dossierFileViewFactory->make(
                $dossier,
                $document,
                DossierFileType::MAIN_DOCUMENT,
            ),
        ]);
    }
}
