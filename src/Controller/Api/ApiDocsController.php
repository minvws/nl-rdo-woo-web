<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiDocsController extends AbstractController
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/docs', name: 'app_api_docs', methods: ['GET'])]
    public function docs(): Response
    {
        return $this->render('api/docs/index.html.twig', [
            'openApiUrl' => $this->urlGenerator->generate('api_doc', ['_format' => 'json']),
        ]);
    }
}
