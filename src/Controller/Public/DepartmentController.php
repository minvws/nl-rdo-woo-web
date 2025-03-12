<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Domain\Department\DepartmentService;
use App\Domain\Publication\Dossier\ViewModel\DossierViewFactory;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Entity\Department;
use App\Service\Search\SearchService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DepartmentController extends AbstractController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DossierViewFactory $dossierViewFactory,
        private readonly SearchParametersFactory $searchParametersFactory,
        private readonly SearchService $searchService,
        private readonly DepartmentService $departmentService,
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

        $facetResult = $this->searchService->searchFacets($searchParameters);

        $loader = $this->twig->getLoader();
        $template = 'public/department/custom/' . $department->getSlug() . '.html.twig';
        if (! $loader->exists($template)) {
            $template = 'public/department/details_default.html.twig';
        }

        return $this->render($template, [
            'recents' => $this->dossierViewFactory->getRecentDossiersForDepartment(5, $department),
            'facets' => $facetResult,
            'department' => $department,
        ]);
    }
}
