<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Form\Elastic\ActivateIndexType;
use App\Form\Elastic\DeleteIndexType;
use App\Form\Elastic\RolloverParametersType;
use App\Service\Elastic\IndexService;
use App\Service\Elastic\Model\RolloverParameters;
use App\Service\Elastic\RolloverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class ElasticController extends AbstractController
{
    public function __construct(
        protected IndexService $indexService,
        protected RolloverService $rolloverService,
        protected TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/elastic', name: 'app_admin_elastic', methods: ['GET'])]
    #[IsGranted('AuthMatrix.elastic.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addItem('Elastic');

        $indices = $this->indexService->list();
        $rolloverDetails = $this->rolloverService->getDetailsFromIndices($indices);

        return $this->render('admin/elastic/index.html.twig', [
            'indices' => $indices,
            'rolloverDetails' => $rolloverDetails,
        ]);
    }

    #[Route('/balie/elastic/create', name: 'app_admin_elastic_create', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.elastic.create')]
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Elastic', 'app_admin_elastic');
        $breadcrumbs->addItem('New elasticsearch rollover');

        $form = $this->createForm(
            RolloverParametersType::class,
            $this->rolloverService->getDefaultRolloverParameters()
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RolloverParameters $data */
            $data = $form->getData();

            $this->rolloverService->initiateRollover($data);

            $this->addFlash('backend', ['success' => $this->translator->trans('Elasticsearch rollover initiated')]);

            return $this->redirectToRoute('app_admin_elastic');
        }

        return $this->render('admin/elastic/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/elastic/{indexName}/details', name: 'app_admin_elastic_details', methods: ['GET'])]
    #[IsGranted('AuthMatrix.elastic.read')]
    public function details(Breadcrumbs $breadcrumbs, string $indexName): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Elastic', 'app_admin_elastic');
        $breadcrumbs->addItem('Details');

        $indices = $this->indexService->find($indexName);
        if (empty($indices)) {
            $this->addFlash('backend', ['error' => 'Invalid elasticsearch index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $index = reset($indices);
        $details = $this->rolloverService->getDetails($index);

        $deleteForm = $this->createForm(
            DeleteIndexType::class,
            null,
            [
                'action' => $this->generateUrl('app_admin_elastic_delete', ['indexName' => $index->name]),
            ]
        );

        return $this->render('admin/elastic/details.html.twig', [
            'index' => $index,
            'details' => $details,
            'deleteForm' => $deleteForm->createView(),
        ]);
    }

    #[Route('/balie/elastic/{indexName}/delete', name: 'app_admin_elastic_delete', methods: ['POST'])]
    #[IsGranted('AuthMatrix.elastic.update')]
    public function delete(string $indexName, Request $request): Response
    {
        $indices = $this->indexService->find($indexName);
        if (empty($indices)) {
            $this->addFlash('backend', ['error' => 'Invalid elasticsearch index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $index = reset($indices);
        if (count($index->aliases) !== 0) {
            $this->addFlash('backend', ['error' => 'Cannot delete an index that is in active use']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $form = $this->createForm(DeleteIndexType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->indexService->delete($indexName);

            $this->addFlash('backend', ['success' => $this->translator->trans("Elasticsearch index $indexName deleted")]);
        }

        return $this->redirectToRoute('app_admin_elastic');
    }

    #[Route('/balie/elastic/{indexName}/live', name: 'app_admin_elastic_live', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.elastic.update')]
    public function makeLive(Breadcrumbs $breadcrumbs, Request $request, string $indexName): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Elastic', 'app_admin_elastic');
        $breadcrumbs->addItem('Promote to Live');

        $indices = $this->indexService->find($indexName);
        if (empty($indices)) {
            $this->addFlash('backend', ['error' => 'Invalid elasticsearch index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $index = reset($indices);
        $details = $this->rolloverService->getDetails($index);

        $form = $this->createForm(ActivateIndexType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->rolloverService->makeLive($indexName);

            $this->addFlash('backend', ['success' => $this->translator->trans('Elasticsearch index switch initiated')]);

            return $this->redirectToRoute('app_admin_elastic');
        }

        return $this->render('admin/elastic/live.html.twig', [
            'index' => $index,
            'details' => $details,
            'form' => $form->createView(),
        ]);
    }
}
