<?php

declare(strict_types=1);

namespace WooMinVWS\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use WooMinVWS\Search\Theme\Covid19Theme;

class VwsController extends AbstractController
{
    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/dossiers', name: 'app_woodecision_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_theme', ['name' => Covid19Theme::URL_NAME]);
    }
}
