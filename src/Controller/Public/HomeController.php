<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Content\Page\ContentPageService;
use Shared\Domain\Content\Page\ContentPageType;
use Shared\Service\Search\Query\Definition\BrowseMainAggregationsQueryDefinition;
use Shared\Service\Search\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly BrowseMainAggregationsQueryDefinition $queryDefinition,
        private readonly ContentPageService $contentPageService,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/', name: 'app_home')]
    public function index(Request $request, Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addItem('global.home');

        // If we have a POST request, we have a search query in the body. Redirect to GET request
        // so we have the q in the query string.
        if ($request->isMethod('POST')) {
            $q = strval($request->request->get('q'));

            // Redirect to GET request, so we have the q in the query string.
            return $this->redirect($this->generateUrl('app_home', ['q' => $q]));
        }

        // From here we always have a 'q' from the query string
        if ($request->query->has('q')) {
            $q = strval($request->query->get('q'));

            return new RedirectResponse($this->generateUrl('app_search', ['q' => $q]));
        }

        return $this->render('public/home/index.html.twig', [
            'intro' => $this->contentPageService->getViewModel(ContentPageType::HOMEPAGE_INTRO),
            'other_publications' => $this->contentPageService->getViewModel(ContentPageType::HOMEPAGE_OTHER_PUBLICATIONS),
            'woo_request' => $this->contentPageService->getViewModel(ContentPageType::HOMEPAGE_WOO_REQUEST),
            'facets' => $this->searchService->getResult($this->queryDefinition),
        ]);
    }
}
