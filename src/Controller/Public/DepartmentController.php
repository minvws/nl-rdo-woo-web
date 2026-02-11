<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentService;
use Shared\Domain\Publication\Dossier\ViewModel\DossierViewFactory;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Search\Query\Definition\BrowseDepartmentAggregationsQueryDefinition;
use Shared\Service\Search\SearchService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

use function hash;

class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly DossierViewFactory $dossierViewFactory,
        private readonly SearchParametersFactory $searchParametersFactory,
        private readonly SearchService $searchService,
        private readonly DepartmentService $departmentService,
        private readonly BrowseDepartmentAggregationsQueryDefinition $aggregationsQueryDefinition,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/bestuursorganen', name: 'app_departments_index')]
    public function index(Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('public.breadcrumbs.departments');

        return $this->render('public/department/index.html.twig', [
            'departments' => $this->departmentService->getPublicDepartments(),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/{slug}', name: 'app_department_detail', priority: -100)]
    public function detail(
        Request $request,
        #[MapEntity(expr: 'repository.findPublicDepartmentBySlug(slug)')] Department $department,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addRouteItem('public.breadcrumbs.departments', 'app_departments_index');
        $breadcrumbs->addItem($department->getShortTagOrName());

        $searchParameters = $this->searchParametersFactory->createForDepartment($department);

        if ($request->isMethod('POST')) {
            $searchParameters = $searchParameters->withQueryString(
                $request->request->getString('q')
            );

            return new RedirectResponse($this->generateUrl(
                'app_search',
                $searchParameters->getQueryParameters()->all(),
            ));
        }

        $facetResult = $this->searchService->getResult($this->aggregationsQueryDefinition, $searchParameters);

        return $this->render(
            'public/department/details.html.twig',
            [
                'departmentLogo' => $department->getFileInfo()->isUploaded()
                    ? $this->generateUrl('app_department_logo_download', [
                        'id' => $department->getId(),
                        'cacheKey' => hash('sha256', (string) $department->getUpdatedAt()->getTimestamp()),
                    ])
                    : null,
                'recents' => $this->dossierViewFactory->getRecentDossiersForDepartment(5, $department),
                'facets' => $facetResult,
                'department' => $department,
            ],
        );
    }
}
