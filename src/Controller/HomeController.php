<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Dossier;
use App\Service\Search\ConfigFactory;
use App\Service\Search\Model\Config;
use App\Service\Search\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class HomeController extends AbstractController
{
    protected EntityManagerInterface $doctrine;
    protected SearchService $searchService;
    protected ConfigFactory $configFactory;

    public function __construct(
        SearchService $searchService,
        EntityManagerInterface $doctrine,
        ConfigFactory $configFactory
    ) {
        $this->doctrine = $doctrine;
        $this->searchService = $searchService;
        $this->configFactory = $configFactory;
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, Breadcrumbs $breadcrumbs): Response
    {
        $breadcrumbs->addItem('Home');

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

        $config = new Config(searchType: Config::TYPE_DOCUMENT);
        $facetResult = $this->searchService->searchFacets($config);

        return $this->render('home/index.html.twig', [
            'doccount' => $this->doctrine->getRepository(Dossier::class)->count([]),
            'recents' => $this->doctrine->getRepository(Dossier::class)->findBy(
                ['status' => Dossier::STATUS_PUBLISHED],
                ['createdAt' => 'DESC'],
                5
            ),
            'facets' => $facetResult,
        ]);
    }
}
