<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminActionService;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Form\Dossier\AdministrationActionsType;
use App\Service\DossierWizard\WizardStatusFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DossierAdministrationController extends AbstractController
{
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly DossierAdminActionService $adminActionService,
        private readonly WizardStatusFactory $wizardStatusFactory,
        private readonly TranslatorInterface $translator,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly BatchDownloadService $batchDownloadService,
    ) {
    }

    #[Route('/balie/admin/dossiers', name: 'app_admin_dossier_administration', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.administration')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('global.publication');

        return $this->render('admin/dossier/administration/index.html.twig', [
            'dossiers' => $this->repository->findAll(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/balie/admin/dossiers/{prefix}/{dossierId}', name: 'app_admin_dossier_administration_details', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.dossier.administration')]
    public function dossier(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AbstractDossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $form = $this->createForm(AdministrationActionsType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DossierAdminAction $adminAction */
            $adminAction = $form->get('action')->getData();
            $this->adminActionService->execute($dossier, $adminAction);

            $this->addFlash(
                'backend',
                ['success' => $this->translator->trans('admin.dossiers.action.action_is_executed')]
            );
        }

        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('global.publication', 'app_admin_dossier_administration');
        $breadcrumbs->addItem($dossier->getDossierNr());

        return $this->render('admin/dossier/administration/details.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
            'workflowStatus' => $this->wizardStatusFactory->getWizardStatus($dossier),
        ]);
    }

    #[Route('/balie/admin/dossiers/downloads', name: 'app_admin_dossier_downloads', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.administration')]
    public function downloads(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('admin.decisions.downloads');

        $dossierEntities = $this->wooDecisionRepository->getPubliclyAvailable();
        $list = [];
        foreach ($dossierEntities as $dossier) {
            $scope = BatchDownloadScope::forWooDecision($dossier);
            $download = $this->batchDownloadService->find($scope);
            $type = $this->batchDownloadService->getType($scope);

            $expectedDownloadCount = $type->getDocumentsQuery($scope)
                ->select('count(doc.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $list[] = [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierNr' => $dossier->getDossierNr(),
                'expectedDownloadCount' => $expectedDownloadCount,
                'downloadFileCount' => $download?->getFileCount(),
                'downloadStatus' => $download?->getStatus(),
                'downloadExpiration' => $download?->getExpiration(),
                'downloadSize' => $download?->getSize(),
            ];
        }

        return $this->render('admin/dossier/administration/downloads.html.twig', [
            'dossiers' => $list,
            'breadcrumbs' => $breadcrumbs,
        ]);
    }
}
