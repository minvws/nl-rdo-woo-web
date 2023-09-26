<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Form\Document\ArchiveFormType;
use App\Form\Document\IngestFormType;
use App\Form\Document\RemoveFormType;
use App\Form\Dossier\DossierType;
use App\Form\Dossier\SearchFormType;
use App\Form\Dossier\StateChangeFormType;
use App\Service\ArchiveService;
use App\Service\DossierService;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\Inventory\ProcessInventoryResult;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierController extends AbstractController
{
    protected const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly PaginatorInterface $paginator,
        private readonly DossierService $dossierService,
        private readonly ArchiveService $archiveService,
        private readonly LoggerInterface $logger,
        private readonly DossierWorkflow $workflow,
    ) {
    }

    #[Route('/balie/dossiers', name: 'app_admin_dossiers', methods: ['GET'])]
    public function index(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Balie', 'app_admin');
        $breadcrumbs->addItem('Dossier management');

        $form = $this->createForm(SearchFormType::class);
        $form->handleRequest($request);

        $query = $this->doctrine->getRepository(Dossier::class)->createQueryBuilder('dos');
        $query->leftJoin('dos.documents', 'doc')->addSelect('doc');
        $query->leftJoin('dos.inquiries', 'inq')->addSelect('inq');

        $this->applyFilter($form, $query);

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            self::MAX_ITEMS_PER_PAGE,
        );

        return $this->render('admin/dossier/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossiers/search', name: 'app_admin_dossiers_search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        $searchTerm = urldecode(strval($request->query->get('q', '')));

        $dossiers = $this->doctrine->getRepository(Dossier::class)->findBySearchTerm($searchTerm, 4);
        $documents = $this->doctrine->getRepository(Document::class)->findBySearchTerm($searchTerm, 4);

        $ret = [
            'results' => json_encode(
                $this->renderView(
                    'admin/dossier/search.html.twig',
                    [
                        'dossiers' => $dossiers,
                        'documents' => $documents,
                    ],
                ),
                JSON_THROW_ON_ERROR,
            ),
        ];

        return new JsonResponse($ret);
    }

    #[Route('/balie/dossier/new', name: 'app_admin_dossier_new', methods: ['GET', 'POST'])]
    public function new(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Balie', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addItem('New dossier');

        $dossier = new Dossier();
        $form = $this->createForm(DossierType::class, $dossier);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $inventoryUpload */
            $inventoryUpload = $form->get('inventory')->getData();
            /** @var UploadedFile $decisionUpload */
            $decisionUpload = $form->get('decision_document')->getData();

            if ($inventoryUpload) {
                $this->logger->info('uploaded inventory file', [
                    'path' => $inventoryUpload->getRealPath(),
                    'original_file' => $inventoryUpload->getClientOriginalName(),
                    'size' => $inventoryUpload->getSize(),
                    'file_hash' => hash_file('sha256', $inventoryUpload->getRealPath()),
                ]);
            }

            $result = $this->dossierService->create($dossier, $inventoryUpload, $decisionUpload);
            if ($result->isSuccessful()) {
                // All is good, we can safely return to dossier list
                $this->addFlash('backend', ['success' => 'Dossier has been created successfully']);

                return $this->redirectToRoute('app_admin_dossiers');
            }

            $this->addFormErrors($form, $result);
        }

        return $this->render('admin/dossier/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}', name: 'app_admin_dossier', methods: ['GET'])]
    public function dossier(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Balie', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addItem('View dossier');

        return $this->render('admin/dossier/view.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->workflow->getStatus($dossier),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit', name: 'app_admin_dossier_edit', methods: ['GET', 'POST'])]
    public function edit(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Balie', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addItem('Edit dossier');

        $response = $this->handleIngestForm($request, $dossier) ??
            $this->handleRemoveForm($request, $dossier) ??
            $this->handleUpdateForm($request, $dossier) ??
            $this->handleStateForm($request, $dossier) ??
            $this->handleArchiveForm($request, $dossier);
        if ($response) {
            return $response;
        }

        $form = $this->createForm(DossierType::class, $dossier, ['edit_mode' => true]);
        $removeForm = $this->createForm(RemoveFormType::class, $dossier);
        $ingestForm = $this->createForm(IngestFormType::class, $dossier);
        $archiveForm = $this->createForm(ArchiveFormType::class, $dossier);
        $stateForm = $this->createForm(StateChangeFormType::class, $dossier);

        return $this->render('admin/dossier/edit.html.twig', [
            'form' => $form->createView(),
            'removeForm' => $removeForm->createView(),
            'ingestForm' => $ingestForm->createView(),
            'archiveForm' => $archiveForm->createView(),
            'stateForm' => $stateForm->createView(),
            'dossier' => $dossier,
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

    protected function handleStateForm(Request $request, Dossier $dossier): ?Response
    {
        $stateForm = $this->createForm(StateChangeFormType::class, $dossier);

        $stateForm->handleRequest($request);
        if (! $stateForm->isSubmitted() || ! $stateForm->isValid()) {
            return null;
        }

        try {
            $this->dossierService->changeState($dossier, strval($stateForm->get('state')->getData()));
        } catch (\Exception $e) {
            $this->addFlash('backend', ['success' => 'Dossier status could not be changed due to incorrect state: ' . $e->getMessage()]);

            return $this->redirectToRoute('app_admin_dossiers');
        }

        $this->addFlash('backend', ['success' => 'Dossier status has been changed']);

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleRemoveForm(Request $request, Dossier $dossier): ?Response
    {
        $removeForm = $this->createForm(RemoveFormType::class, $dossier);

        $removeForm->handleRequest($request);
        if (! $removeForm->isSubmitted() || ! $removeForm->isValid()) {
            return null;
        }

        $this->dossierService->remove($dossier);
        $this->addFlash('backend', ['success' => 'Dossier has been removed']);

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleIngestForm(Request $request, Dossier $dossier): ?Response
    {
        $ingestForm = $this->createForm(IngestFormType::class, $dossier);

        $ingestForm->handleRequest($request);
        if (! $ingestForm->isSubmitted() || ! $ingestForm->isValid()) {
            return null;
        }

        $this->dossierService->dispatchIngest($dossier);

        $this->addFlash('backend', ['success' => 'Dossier is scheduled for ingestion']);

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleArchiveForm(Request $request, Dossier $dossier): ?Response
    {
        $archiveForm = $this->createForm(ArchiveFormType::class, $dossier);

        $archiveForm->handleRequest($request);
        if (! $archiveForm->isSubmitted() || ! $archiveForm->isValid()) {
            return null;
        }

        $this->archiveService->deleteDossierArchives($dossier);
        $this->archiveService->createArchiveForCompleteDossier($dossier);

        $this->addFlash('backend', ['success' => 'Creating dossier archive']);

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleUpdateForm(Request $request, Dossier $dossier): ?Response
    {
        $form = $this->createForm(DossierType::class, $dossier, ['edit_mode' => true]);

        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var UploadedFile $inventoryUpload */
        $inventoryUpload = $form->get('inventory')->getData();
        /** @var UploadedFile $decisionUpload */
        $decisionUpload = $form->get('decision_document')->getData();

        if ($inventoryUpload) {
            $this->logger->info('uploaded inventory file', [
                'path' => $inventoryUpload->getRealPath(),
                'original_file' => $inventoryUpload->getClientOriginalName(),
                'size' => $inventoryUpload->getSize(),
                'file_hash' => hash_file('sha256', $inventoryUpload->getRealPath()),
            ]);
        }

        $result = $this->dossierService->update($dossier, $inventoryUpload, $decisionUpload);

        if ($result->isSuccessful()) {
            // All is good, we can safely return to dossier list
            $this->addFlash('backend', ['success' => 'Dossier has been updated successfully']);

            return $this->redirectToRoute('app_admin_dossiers');
        }

        // Add errors to form
        $this->addFormErrors($form->get('inventory'), $result);

        return null;
    }

    protected function addFormErrors(FormInterface $form, ProcessInventoryResult $result): void
    {
        foreach ($result->getGenericErrors() as $errorMessage) {
            $form->addError(new FormError($errorMessage));
        }

        foreach ($result->getRowErrors() as $lineNum => $lineErrors) {
            foreach ($lineErrors as $error) {
                $form->addError(new FormError(sprintf('Line %d: %s', $lineNum, $error)));
            }
        }
    }
}
