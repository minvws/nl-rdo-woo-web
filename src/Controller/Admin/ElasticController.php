<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\ElasticConfig;
use App\Form\Elastic\ActivateIndexType;
use App\Form\Elastic\CreateRolloverType;
use App\Message\InitializeElasticRolloverMessage;
use App\Message\SetElasticAliasMessage;
use App\Model\CreateElasticsearchRollover;
use App\Service\Elastic\IndexService;
use App\Service\Elastic\MappingService;
use App\Service\Elastic\RolloverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ElasticController extends AbstractController
{
    public function __construct(
        protected IndexService $indexService,
        protected MappingService $mappingService,
        protected RolloverService $rolloverService,
        protected TranslatorInterface $translator,
        protected MessageBusInterface $bus,
    ) {
    }

    #[Route('/balie/elastic', name: 'app_admin_elastic', methods: ['GET'])]
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
    public function create(Breadcrumbs $breadcrumbs, Request $request): Response
    {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Elastic', 'app_admin_elastic');
        $breadcrumbs->addItem('New elasticsearch rollover');

        $latestMappingVersion = $this->mappingService->getLatestMappingVersion();
        $createRollover = new CreateElasticsearchRollover(
            mappingVersion: $latestMappingVersion,
        );
        $form = $this->createForm(CreateRolloverType::class, $createRollover);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CreateElasticsearchRollover $data */
            $data = $form->getData();

            $indexName = ElasticConfig::INDEX_PREFIX . date('Ymd-His');
            $message = new InitializeElasticRolloverMessage(
                mappingVersion: $data->getMappingVersion(),
                indexName: $indexName,
            );
            $this->bus->dispatch($message);

            $this->addFlash('backend', ['success' => $this->translator->trans('Elasticsearch rollover initiated')]);

            return $this->redirectToRoute('app_admin_elastic');
        }

        return $this->render('admin/elastic/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/elastic/{indexName}/details', name: 'app_admin_elastic_details', methods: ['GET'])]
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

        return $this->render('admin/elastic/details.html.twig', [
            'index' => $index,
            'details' => $details,
        ]);
    }

    #[Route('/balie/elastic/{indexName}/live', name: 'app_admin_elastic_live', methods: ['GET', 'POST'])]
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
            $message = new SetElasticAliasMessage(
                indexName: $indexName,
                aliasName: ElasticConfig::READ_INDEX,
            );
            $this->bus->dispatch($message);

            $message = new SetElasticAliasMessage(
                indexName: $indexName,
                aliasName: ElasticConfig::WRITE_INDEX,
            );
            $this->bus->dispatch($message);

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
