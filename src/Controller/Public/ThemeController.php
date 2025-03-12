<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Domain\Search\Query\SearchParametersFactory;
use App\Domain\Search\Theme\ThemeInterface;
use App\Domain\Search\Theme\ThemeManager;
use App\Service\Search\Result\Result;
use App\Service\Search\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class ThemeController extends AbstractController
{
    public function __construct(
        private readonly ThemeManager $themeManager,
        private readonly SearchParametersFactory $searchParametersFactory,
        private readonly SearchService $searchService,
    ) {
    }

    #[Route('/thema/{name}', name: 'app_theme')]
    public function search(
        string $name,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        // If we have a POST request, we have a search query in the body. Redirect to GET request
        // so we have the q in the query string.
        if ($request->isMethod('POST')) {
            $q = strval($request->request->get('q'));

            // Redirect to GET request, so we have the q in the query string.
            return $this->redirect($this->generateUrl(
                'app_theme',
                array_merge($request->query->all(), ['q' => $q, 'page' => null, 'name' => $name])
            ));
        }

        $theme = $this->getTheme($name);
        $result = $this->getResult($request, $theme);

        $breadcrumbs->addRouteItem('global.home', 'app_home');
        $breadcrumbs->addItem($theme->getPageTitleTranslationKey());

        if ($result->hasFailed()) {
            return $this->render('public/search/result-failure.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('public/theme/result.html.twig', [
            'result' => $result,
            'theme' => $this->themeManager->getView($theme),
            'ajaxResultsUrl' => $this->generateUrl(
                'app_theme_result_ajax',
                ['name' => $theme->getUrlName()],
            ),
        ]);
    }

    /**
     * Ajax endpoint for search results within a theme.
     */
    #[Route('thema/{name}/_result', name: 'app_theme_result_ajax', methods: ['GET'])]
    public function ajaxResult(
        string $name,
        Request $request,
    ): JsonResponse {
        $theme = $this->getTheme($name);
        $result = $this->getResult($request, $theme);

        $responseData = [];
        $responseData['facets'] = json_encode(
            $this->renderView(
                'public/search/facet.html.twig',
                ['result' => $result]
            ),
            JSON_THROW_ON_ERROR,
        );

        // If the search failed, we show a different template
        if ($result->hasFailed()) {
            $template = 'public/search/result-failure-without-base.html.twig';
        } else {
            $template = 'public/search/entries.html.twig';
        }

        $responseData['results'] = json_encode($this->renderView($template, [
            'result' => $result,
        ]), JSON_THROW_ON_ERROR);

        return new JsonResponse($responseData);
    }

    private function getTheme(string $name): ThemeInterface
    {
        $theme = $this->themeManager->getThemeByUrlName($name);
        if ($theme === null) {
            throw new NotFoundHttpException();
        }

        return $theme;
    }

    private function getResult(Request $request, ThemeInterface $theme): Result
    {
        $searchParameters = $this->searchParametersFactory
            ->createFromRequest($request)
            ->withBaseQueryConditions($theme->getBaseQueryConditions());

        return $this->searchService->search($searchParameters);
    }
}
