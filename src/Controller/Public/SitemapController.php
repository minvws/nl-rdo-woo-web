<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Department\DepartmentService;
use Shared\Service\Search\Query\Definition\BrowseAllAggregationsQueryDefinition;
use Shared\Service\Search\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly BrowseAllAggregationsQueryDefinition $aggregationsQueryDefinition,
        private readonly DepartmentService $departmentService,
        private readonly SearchService $searchService,
    ) {
    }

    #[Route('/sitemap', name: 'app_sitemap')]
    public function index(): Response
    {
        return $this->render('public/sitemap/index.html.twig', [
            'departments' => $this->departmentService->getPublicDepartments(),
            'result' => $this->searchService->getResult($this->aggregationsQueryDefinition),
        ]);
    }
}
