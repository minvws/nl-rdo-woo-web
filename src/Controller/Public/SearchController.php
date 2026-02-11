<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Search\Query\Definition\BrowseAllAggregationsQueryDefinition;
use Shared\Service\Search\Query\Definition\SearchAllQueryDefinition;
use Shared\Service\Search\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

use function array_merge;
use function json_encode;
use function strval;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly SearchParametersFactory $searchParametersFactory,
        private readonly BrowseAllAggregationsQueryDefinition $aggregationsQueryDefinition,
        private readonly SearchAllQueryDefinition $searchAllQueryDefinition,
    ) {
    }

    /**
     * Ajax endpoint for search results.
     */
    #[Route('/_result', name: 'app_search_result_ajax', methods: ['GET'])]
    public function ajaxResult(Request $request): JsonResponse
    {
        // Do the search
        $searchParameters = $this->searchParametersFactory->createFromRequest($request);
        $result = $this->searchService->getResult($this->searchAllQueryDefinition, $searchParameters);

        // We return an json with 2 html blobs: facets and result. These are loaded in the correct spots in the frontend.
        $ret = [];
        $ret['facets'] = json_encode($this->renderView('public/search/facet.html.twig', [
            'result' => $result,
        ]));

        // If the search failed, we show a different template
        if ($result->hasFailed()) {
            $template = 'public/search/result-failure-without-base.html.twig';
        } else {
            $template = 'public/search/entries.html.twig';
        }

        $ret['results'] = json_encode($this->renderView($template, [
            'result' => $result,
        ]));

        return new JsonResponse($ret);
    }

    #[Route('/zoeken', name: 'app_search')]
    public function search(Request $request, Breadcrumbs $breadcrumbs): Response
    {
        // If we have a POST request, we have a search query in the body. Redirect to GET request
        // so we have the q in the query string.
        if ($request->isMethod('POST')) {
            $q = strval($request->request->get('q'));

            // Redirect to GET request, so we have the q in the query string.
            return $this->redirect($this->generateUrl(
                'app_search',
                array_merge($request->query->all(), ['q' => $q, 'page' => null])
            ));
        }

        $searchParameters = $this->searchParametersFactory->createFromRequest($request);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('public.search.label');

        $result = $this->searchService->getResult($this->searchAllQueryDefinition, $searchParameters);
        if ($result->hasFailed()) {
            return $this->render('public/search/result-failure.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('public/search/result.html.twig', [
            'result' => $result,
            'ajaxResultsUrl' => $this->generateUrl('app_search_result_ajax'),
        ]);
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/alle-categorieën', name: 'app_browse', options: ['sitemap' => ['priority' => 0.7]])]
    public function browse(Request $request, Breadcrumbs $breadcrumbs): Response
    {
        // If we have a POST request, we have a search query in the body. Redirect to GET request
        // so we have the q in the query string.
        if ($request->isMethod('POST')) {
            $q = strval($request->request->get('q'));

            // Redirect to GET request, so we have the q in the query string.
            return $this->redirect($this->generateUrl('app_browse', ['q' => $q]));
        }

        // From here we always have a 'q' from the query string
        if ($request->query->has('q')) {
            $q = strval($request->query->get('q'));

            return new RedirectResponse($this->generateUrl('app_search', ['q' => $q]));
        }

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem('public.global.label.all_categories');

        $result = $this->searchService->getResult($this->aggregationsQueryDefinition);
        if ($result->hasFailed()) {
            return $this->render('public/search/result-failure.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('public/search/browse-facets.html.twig', [
            'result' => $result,
        ]);
    }
}
