<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Service\Search\ConfigFactory;
use App\Service\Search\Model\Config;
use App\Service\Search\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class SearchController extends AbstractController
{
    protected SearchService $searchService;
    protected ConfigFactory $configFactory;

    public function __construct(SearchService $searchService, ConfigFactory $configFactory)
    {
        $this->searchService = $searchService;
        $this->configFactory = $configFactory;
    }

    /**
     * Ajax endpoint for search results.
     */
    #[Route('/_result', name: 'app_search_result_ajax', methods: ['GET'])]
    public function ajaxResult(Request $request): JsonResponse
    {
        // Do the search
        $config = $this->configFactory->createFromRequest($request);
        $result = $this->searchService->search($config);

        // We return an json with 2 html blobs: facets and result. These are loaded in the correct spots in the frontend.
        $ret = [];
        $ret['facets'] = json_encode($this->renderView('search/facet.html.twig', [
            'result' => $result,
        ]));

        // If the search failed, we show a different template
        if ($result->hasFailed()) {
            $template = 'search/result-failure.html.twig';
        } else {
            $template = 'search/entries.html.twig';
        }

        $ret['results'] = json_encode($this->renderView($template, [
            'result' => $result,
        ]));

        return new JsonResponse($ret);
    }

    /**
     * Ajax endpoint for light version of search results.
     */
    #[Route('/_result_minimalistic', name: 'app_search_result_minimalistic_ajax', methods: ['GET'])]
    public function ajaxResultMinimalistic(Request $request): Response
    {
        // Do the search
        $config = $this->configFactory->createFromRequest(
            $request,
            pagination: false,
            aggregations: false,
        );
        $result = $this->searchService->search($config);

        // If the search failed, we show a different template
        // @todo: this was copied from the normal search, but should not we just throw an error?
        if ($result->hasFailed()) {
            $template = 'search/result-failure.html.twig';
        } else {
            $template = 'search/entries-minimalistic.html.twig';
        }

        return $this->render($template, [
            'result' => $result,
        ]);
    }

    #[Route('/search', name: 'app_search')]
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

        $config = $this->configFactory->createFromRequest($request);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        if ($config->searchType === Config::TYPE_DOSSIER) {
            $breadcrumbs->addItem('public.global.label.overview_publications');
        } else {
            $breadcrumbs->addItem('public.search.label');
        }

        $result = $this->searchService->search($config);
        if ($result->hasFailed()) {
            return $this->render('search/result-failure.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('search/result.html.twig', [
            'result' => $result,
        ]);
    }

    #[Cache(public: true, maxage: 600, mustRevalidate: true)]
    #[Route('/browse', name: 'app_browse')]
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

        $result = $this->searchService->retrieveExtendedFacets();
        if ($result->hasFailed()) {
            return $this->render('search/result-failure.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('search/browse-facets.html.twig', [
            'result' => $result,
        ]);
    }
}
