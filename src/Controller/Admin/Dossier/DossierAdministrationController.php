<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionService;
use Shared\Domain\Publication\Dossier\DossierBreadcrumbFactory;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Form\Dossier\AdministrationActionsType;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

use function sprintf;

class DossierAdministrationController extends AbstractController
{
    public const string ROUTE_NAME_DOSSIER_ADMINISTRATION = 'app_admin_dossier_administration';
    public const string ROUTE_NAME_DOSSIER_ADMINISTRATION_DETAILS = 'app_admin_dossier_administration_details';
    public const string ROUTE_NAME_DOSSIER_DOWNLOADS = 'app_admin_dossier_downloads';

    public function __construct(
        private readonly DossierRepository $repository,
        private readonly DossierAdminActionService $adminActionService,
        private readonly WizardStatusFactory $wizardStatusFactory,
        private readonly TranslatorInterface $translator,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DossierBreadcrumbFactory $breadcrumbFactory,
    ) {
    }

    #[Route('/balie/admin/dossiers', self::ROUTE_NAME_DOSSIER_ADMINISTRATION, methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.administration')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('global.publications');

        return $this->render('admin/dossier/administration/index.html.twig', [
            'dossiers' => $this->repository->findAll(),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    #[Route('/balie/admin/dossiers/{documentPrefix}/{dossierNumber}', self::ROUTE_NAME_DOSSIER_ADMINISTRATION_DETAILS, methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.dossier.administration')]
    public function dossier(
        #[MapEntity(mapping: ['documentPrefix' => 'documentPrefix', 'dossierNumber' => 'dossierNumber'])] AbstractDossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $form = $this->createForm(AdministrationActionsType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adminAction = $form->get('action')->getData();
            Assert::isInstanceOf($adminAction, DossierAdminAction::class);

            $this->adminActionService->execute($dossier, $adminAction);

            $this->addFlash(
                'backend',
                ['success' => $this->translator->trans('admin.dossiers.action.action_is_executed')],
            );
        }

        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('global.publications', 'app_admin_dossier_administration');
        $breadcrumbs->addItem($this->breadcrumbFactory->forDossier($dossier));

        return $this->render('admin/dossier/administration/details.html.twig', [
            'dossier' => $dossier,
            'form' => $form,
            'breadcrumbs' => $breadcrumbs,
            'workflowStatus' => $this->wizardStatusFactory->getWizardStatus($dossier),
            'confirmations' => $this->getConfirmations($dossier),
        ]);
    }

    #[Route('/balie/admin/dossiers/downloads', self::ROUTE_NAME_DOSSIER_DOWNLOADS, methods: ['GET'])]
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
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
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

    /**
     * @return array<string, string|null>
     */
    private function getConfirmations(AbstractDossier $dossier): array
    {
        $confirmations = [];
        foreach ($this->adminActionService->getAvailableAdminActions($dossier) as $action) {
            $confirmation = null;
            if ($this->adminActionService->needsConfirmation($action)) {
                $confirmation = $this->translator->trans(
                    sprintf('admin.dossiers.action.confirmation.%s', $action->value),
                );
            }

            $confirmations[$action->value] = $confirmation;
        }

        return $confirmations;
    }
}
