<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Enum\PublicationStatus;
use App\Form\Dossier\SearchFormType;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierController extends AbstractController
{
    use DossierAuthorizationTrait;

    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly PaginatorInterface $paginator,
        private readonly DossierWorkflow $workflow,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    #[Route('/balie/dossiers', name: 'app_admin_dossiers', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $statuses = [];
        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_PUBLISHED_DOSSIERS)) {
            $statuses = array_merge($statuses, PublicationStatus::nonConceptCases());
        }
        if ($this->authorizationMatrix->getFilter(AuthorizationMatrix::FILTER_UNPUBLISHED_DOSSIERS)) {
            $statuses = array_merge($statuses, PublicationStatus::conceptCases());
        }

        $query = $this->dossierRepository->getDossiersForOrganisationQueryBuilder(
            $this->authorizationMatrix->getActiveOrganisation(),
            $statuses
        );

        $this->applyFilter($form, $query);

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

        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        $dossiers = $this->dossierRepository->findBySearchTerm($searchTerm, 4, $organisation);
        $documents = $this->documentRepository->findBySearchTerm($searchTerm, 4, $organisation);

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search.html.twig',
                    [
                        'dossiers' => $dossiers,
                        'documents' => $documents,
                        'searchTerm' => $searchTerm,
                    ],
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

        $organisation = $this->authorizationMatrix->getActiveOrganisation();

        $dossiers = $this->dossierRepository->findBySearchTerm($searchTerm, 4, $organisation);

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search_link.html.twig',
                    [
                        'dossiers' => $dossiers,
                        'searchTerm' => $searchTerm,
                    ],
                ),
                JSON_THROW_ON_ERROR,
            ),
        ];

        return new JsonResponse($ret);
    }

    #[Route('/balie/dossier/{prefix}/{dossierId}', name: 'app_admin_dossier', methods: ['GET'])]
    #[IsGranted('AuthMatrix.dossier.read')]
    public function dossier(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        return $this->render('admin/dossier/view.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->workflow->getStatus($dossier),
        ]);
    }

    protected function applyFilter(FormInterface $form, QueryBuilder $queryBuilder): void
    {
        /** @var string[] $statusFilters */
        $statusFilters = $form->get('status')->getData();
        if (is_array($statusFilters) && count($statusFilters) > 0) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->in('dos.status', ':statuses'))
                ->setParameter('statuses', $statusFilters);
        }

        /** @var ArrayCollection $departmentFilters */
        $departmentFilters = $form->get('department')->getData();
        if ($departmentFilters !== null && ! $departmentFilters->isEmpty()) {
            $queryBuilder
                ->innerJoin('dos.departments', 'dep')
                ->andWhere($queryBuilder->expr()->in('dep.id', ':departments'))
                ->setParameter('departments', $departmentFilters->toArray());
        }
    }
}
