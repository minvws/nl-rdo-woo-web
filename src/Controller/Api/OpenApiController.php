<?php

declare(strict_types=1);

namespace Shared\Controller\Api;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Shared\Api\Publication\V1\PublicationV1Api;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class OpenApiController extends AbstractController
{
    public function __construct(
        private readonly OpenApiFactoryInterface $factory,
        private readonly SerializerInterface $serializer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(path: PublicationV1Api::OPENAPI_URLS, name: PublicationV1Api::OPENAPI_ROUTE_NAME, methods: ['GET'])]
    public function publicationV1OpenApi(): JsonResponse
    {
        $openApi = ($this->factory)(['filter_tags' => PublicationV1Api::API_TAG]);

        return new JsonResponse(
            data: $this->serializer->serialize($openApi, 'json'),
            status: 200,
            json: true,
        );
    }

    #[Route(path: PublicationV1Api::DOCS_URLS, name: PublicationV1Api::DOCS_ROUTE_NAME, methods: ['GET'])]
    public function publicationV1Docs(): Response
    {
        return $this->render('api/docs/index.html.twig', [
            'openApiUrl' => $this->urlGenerator->generate('api_publication_v1_open_api_json'),
        ]);
    }

    #[Route(path: '/admin/api/openapi.json', name: 'api_admin_open_api_json', methods: ['GET'])]
    public function adminOpenApi(): JsonResponse
    {
        $openApi = ($this->factory)(['filter_tags' => 'admin']);

        return new JsonResponse(
            data: $this->serializer->serialize($openApi, 'json'),
            status: 200,
            json: true,
        );
    }

    #[Route(path: '/admin/api/docs', name: 'api_admin_docs', methods: ['GET'])]
    public function adminDocs(): Response
    {
        return $this->render('api/docs/index.html.twig', [
            'openApiUrl' => $this->urlGenerator->generate('api_admin_open_api_json'),
        ]);
    }
}
