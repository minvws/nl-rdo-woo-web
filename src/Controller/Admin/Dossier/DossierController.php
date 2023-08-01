<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Form\Document\IngestFormType;
use App\Form\Document\RemoveFormType;
use App\Form\Dossier\DossierType;
use App\Form\Dossier\SearchFormType;
use App\Form\Dossier\StateChangeFormType;
use App\Service\DossierService;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected PaginatorInterface $paginator;
    protected DossierService $dossierService;
    protected IngestService $ingester;
    protected ElasticService $elasticService;

    public function __construct(
        EntityManagerInterface $doctrine,
        PaginatorInterface $paginator,
        DossierService $dossierService,
        IngestService $ingester,
        ElasticService $elasticService,
    ) {
        $this->doctrine = $doctrine;
        $this->paginator = $paginator;
        $this->dossierService = $dossierService;
        $this->ingester = $ingester;
        $this->elasticService = $elasticService;
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
            10
        );

        return $this->render('admin/dossier/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
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
            /** @var UploadedFile $file */
            $file = $form->get('inventory')->getData();
            $errors = $this->dossierService->create($dossier, $file);

            if (! count($errors)) {
                // All is good, we can safely return to dossier list
                $this->addFlash('success', 'Dossier has been created successfully');

                return $this->redirectToRoute('app_admin_dossiers');
            }

            $this->addFormErrors($form, $errors);
        }

        return $this->render('admin/dossier/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}', name: 'app_admin_dossier_edit', methods: ['GET', 'POST'])]
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
            $this->handleStateForm($request, $dossier);
        if ($response) {
            return $response;
        }

        $form = $this->createForm(DossierType::class, $dossier, ['edit_mode' => true]);
        $removeForm = $this->createForm(RemoveFormType::class, $dossier);
        $ingestForm = $this->createForm(IngestFormType::class, $dossier);
        $stateForm = $this->createForm(StateChangeFormType::class, $dossier);

        return $this->render('admin/dossier/edit.html.twig', [
            'form' => $form->createView(),
            'removeForm' => $removeForm->createView(),
            'ingestForm' => $ingestForm->createView(),
            'stateForm' => $stateForm->createView(),
            'dossier' => $dossier,
        ]);
    }

    protected function applyFilter(FormInterface $form, QueryBuilder $queryBuilder): void
    {
        $searchTerm = strval($form->get('searchterm')->getData());
        if (! empty($searchTerm)) {
            $queryBuilder->andWhere('LOWER(dos.title) LIKE :filter 
                OR LOWER(dos.status) LIKE :filter 
                OR LOWER(dos.dossierNr) LIKE :filter 
                OR inq.casenr LIKE :filter')
                ->setParameter('filter', '%' . strtolower($searchTerm) . '%');
        }

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
            $this->addFlash('danger', 'Dossier status could not be changed due to incorrect state: ' . $e->getMessage());

            return $this->redirectToRoute('app_admin_dossiers');
        }

        $this->addFlash('success', 'Dossier status has been changed');

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
        $this->addFlash('success', 'Dossier has been removed');

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleIngestForm(Request $request, Dossier $dossier): ?Response
    {
        $ingestForm = $this->createForm(IngestFormType::class, $dossier);

        $ingestForm->handleRequest($request);
        if (! $ingestForm->isSubmitted() || ! $ingestForm->isValid()) {
            return null;
        }

        $this->elasticService->updateDossier($dossier, false);
        foreach ($dossier->getDocuments() as $document) {
            $this->ingester->ingest($document, new Options());
        }

        $this->addFlash('success', 'Dossier is scheduled for ingestion');

        return $this->redirectToRoute('app_admin_dossiers');
    }

    protected function handleUpdateForm(Request $request, Dossier $dossier): ?Response
    {
        $form = $this->createForm(DossierType::class, $dossier, ['edit_mode' => true]);

        $form->handleRequest($request);
        if (! $form->isSubmitted() || ! $form->isValid()) {
            return null;
        }

        /** @var UploadedFile $file */
        $file = $form->get('inventory')->getData();
        $errors = $this->dossierService->update($dossier, $file);

        if (! count($errors)) {
            // All is good, we can safely return to dossier list
            $this->addFlash('success', 'Dossier has been updated successfully');

            return $this->redirectToRoute('app_admin_dossiers');
        }

        // Add errors to form
        $this->addFormErrors($form->get('inventory'), $errors);

        return null;
    }

    /**
     * @param array<int, string[]> $errors
     */
    protected function addFormErrors(FormInterface $form, array $errors): void
    {
        // Add all errors to the form
        foreach ($errors as $lineNum => $lineErrors) {
            foreach ($lineErrors as $error) {
                if ($lineNum == 0) {
                    $form->addError(new FormError($error));
                } else {
                    $form->addError(new FormError(sprintf('Line %d: %s', $lineNum, $error)));
                }
            }
        }
    }
}
