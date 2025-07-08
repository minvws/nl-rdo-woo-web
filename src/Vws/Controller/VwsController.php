<?php

declare(strict_types=1);

namespace App\Vws\Controller;

use App\Vws\Search\Theme\Covid19Theme;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

class VwsController extends AbstractController
{
    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/dossiers', name: 'app_woodecision_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_theme', ['name' => Covid19Theme::URL_NAME]);
    }
}
