<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use App\Domain\Publication\Dossier\Admin\DossierListingService;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Form\Dossier\SearchFormType;
use App\Service\DossierWizard\DossierWizardHelper;
use App\ViewModel\Factory\ApplicationMode;
use App\ViewModel\Factory\AttachmentViewFactory;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly DossierListingService $listingService,
        private readonly DossierSearchService $searchService,
        private readonly PaginatorInterface $paginator,
        private readonly DossierWizardHelper $wizardHelper,
        private readonly DossierTypeManager $dossierTypeManager,
        private readonly AttachmentViewFactory $attachmentViewFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $publicBaseUrl,
    ) {
    }

    #[Route('/balie/dossiers', name: 'app_admin_dossiers', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        /** @var ?DossierFilterParameters $filterParameters */
        $filterParameters = $form->getData();

        $query = $this->listingService->getFilteredListingQuery($filterParameters);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            self::MAX_ITEMS_PER_PAGE,
        );

        return $this->render('admin/dossier/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
            'formData' => $form->getData(),
        ]);
    }

    #[Route('/balie/dossiers/search', name: 'app_admin_dossiers_search', methods: ['POST'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function search(Request $request): Response
    {
        $searchTerm = urldecode(strval($request->getPayload()->get('q', '')));

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search.html.twig',
                    [
                        'dossiers' => $this->searchService->searchDossiers($searchTerm),
                        'documents' => $this->searchService->searchDocuments($searchTerm),
                        'searchTerm' => $searchTerm,
                    ]
                ),
                JSON_THROW_ON_ERROR,
            ),
        ];

        return new JsonResponse($ret);
    }

    #[Route('/balie/dossiers/search/link', name: 'app_admin_dossiers_search_link', methods: ['POST'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function searchLink(Request $request): Response
    {
        $searchTerm = urldecode(strval($request->getPayload()->get('q', '')));

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search_link.html.twig',
                    [
                        'dossiers' => $this->searchService->searchDossiers($searchTerm),
                        'searchTerm' => $searchTerm,
                    ],
                ),
                JSON_THROW_ON_ERROR,
            ),
        ];

        return new JsonResponse($ret);
    }

    #[Route('/balie/dossier/overview/{prefix}/{dossierId}', name: 'app_admin_dossier', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function dossier(
        #[MapEntity(
            mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'],
            class: AbstractDossier::class,
        )]
        EntityWithAttachments&AbstractDossier $dossier,
    ): Response {
        /** @TODO move to ViewModel */
        $detailsEndpointName = match ($dossier->getType()) {
            DossierType::WOO_DECISION => 'app_woodecision_detail',
            DossierType::COVENANT => 'app_covenant_detail',
        };

        return $this->render(
            'admin/dossier/' . $dossier->getType()->value . '/view.html.twig',
            [
                'attachments' => $this->attachmentViewFactory->makeCollection(
                    $dossier,
                    ApplicationMode::ADMIN,
                ),
                'dossier' => $dossier,
                'workflowStatus' => $this->wizardHelper->getStatus($dossier),
                'publicDossierUrl' => $this->urlGenerator->generate(
                    $detailsEndpointName,
                    ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()],
                    UrlGenerator::ABSOLUTE_URL,
                ),
            ]
        );
    }

    #[Route(
        path: '/balie/dossier/overview/{prefix}/{dossierId}/publication-confirmation',
        name: 'app_admin_dossier_publication_confirmation',
        methods: ['GET']
    )]
    #[IsGranted('AuthMatrix.dossier.read', subject: 'dossier')]
    public function publicationConfirmation(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AbstractDossier $dossier,
    ): Response {
        /** @TODO move to ViewModel */
        $detailsEndpointName = match ($dossier->getType()) {
            DossierType::WOO_DECISION => 'app_woodecision_detail',
            DossierType::COVENANT => 'app_covenant_detail',
        };

        $detailsEndpointPath = $this->urlGenerator->generate(
            $detailsEndpointName,
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()],
        );

        return $this->render('admin/dossier/' . $dossier->getType()->value . '/publication-confirmation.html.twig', [
            'dossier' => $dossier,
            'publicDossierUrl' => sprintf('%s%s', $this->publicBaseUrl, $detailsEndpointPath),
        ]);
    }

    #[Route('/balie/dossier/create', name: 'app_admin_dossier_create', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.create')]
    public function create(): Response
    {
        $typeConfigs = $this->dossierTypeManager->getAvailableConfigs();
        if (count($typeConfigs) === 1) {
            $typeConfig = reset($typeConfigs);

            return $this->redirectToRoute($typeConfig->getCreateRouteName());
        }

        return $this->render('admin/dossier/create.html.twig', [
            'typeConfigs' => $typeConfigs,
        ]);
    }
}
