<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Domain\Search\Index\Rollover\RolloverParameters;
use Shared\Domain\Search\Index\Rollover\RolloverService;
use Shared\Form\Elastic\ActivateIndexType;
use Shared\Form\Elastic\DeleteIndexType;
use Shared\Form\Elastic\RolloverParametersType;
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
        protected ElasticIndexManager $indexService,
        protected RolloverService $rolloverService,
        protected TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/elastic', name: 'app_admin_elastic', methods: ['GET'])]
    #[IsGranted('AuthMatrix.elastic.read')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addItem('admin.elastic.generic');

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
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.elastic.generic', 'app_admin_elastic');
        $breadcrumbs->addItem('admin.elastic.new_rollover');

        $form = $this->createForm(
            RolloverParametersType::class,
            $this->rolloverService->getDefaultRolloverParameters()
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RolloverParameters $data */
            $data = $form->getData();

            $this->rolloverService->initiateRollover($data);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.elastic.rollover_initiated')]);

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
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.elastic.generic', 'app_admin_elastic');
        $breadcrumbs->addItem('global.details');

        $indices = $this->indexService->find($indexName);
        if ($indices === []) {
            $this->addFlash('backend', ['danger' => 'admin.elastic.invalid_index']);

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
        if ($indices === []) {
            $this->addFlash('backend', ['danger' => 'admin.elastic.invalid_index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $index = reset($indices);
        if ($index->aliases !== []) {
            $this->addFlash('backend', ['danger' => 'admin.elastic.cannot_delete_active_index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $form = $this->createForm(DeleteIndexType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->indexService->delete($indexName);

            $this->addFlash(
                'backend',
                ['success' => $this->translator->trans('admin.elastic.index_deleted', ['index' => $indexName])]
            );
        }

        return $this->redirectToRoute('app_admin_elastic');
    }

    #[Route('/balie/elastic/{indexName}/live', name: 'app_admin_elastic_live', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.elastic.update')]
    public function makeLive(Breadcrumbs $breadcrumbs, Request $request, string $indexName): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('global.admin', 'app_admin');
        $breadcrumbs->addRouteItem('admin.elastic.generic', 'app_admin_elastic');
        $breadcrumbs->addItem('admin.elastic.promote_to_live');

        $indices = $this->indexService->find($indexName);
        if ($indices === []) {
            $this->addFlash('backend', ['danger' => 'admin.elastic.invalid_index']);

            return $this->redirectToRoute('app_admin_elastic');
        }

        $index = reset($indices);
        $details = $this->rolloverService->getDetails($index);

        $form = $this->createForm(ActivateIndexType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->rolloverService->makeLive($indexName);

            $this->addFlash('backend', ['success' => $this->translator->trans('admin.elastic.switch_initiated')]);

            return $this->redirectToRoute('app_admin_elastic');
        }

        return $this->render('admin/elastic/live.html.twig', [
            'index' => $index,
            'details' => $details,
            'form' => $form->createView(),
        ]);
    }
}
