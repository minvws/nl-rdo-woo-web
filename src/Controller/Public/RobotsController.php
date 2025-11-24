<?php

declare(strict_types=1);

namespace Shared\Controller\Public;

use Shared\Domain\Robots\RobotsViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Annotation\Route;

final class RobotsController extends AbstractController
{
    public function __construct(
        private readonly RobotsViewFactory $robotsViewFactory,
    ) {
    }

    #[Cache(maxage: 600, public: true, mustRevalidate: true)]
    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function index(): Response
    {
        $robotsViewModel = $this->robotsViewFactory->make();

        $response = new Response(
            $this->renderView('public/robots/robots.txt.twig', ['robots' => $robotsViewModel]),
            200,
        );
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }
}
