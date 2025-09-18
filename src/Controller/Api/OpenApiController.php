<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\OpenApi\GroupedOpenApiFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class OpenApiController extends AbstractController
{
    public function __construct(
        public readonly GroupedOpenApiFactory $factory,
        public readonly SerializerInterface $serializer,
    ) {
    }

    #[Route('/api/publication/v1/openapi.json', name: 'open_api_publication_json', methods: ['GET'])]
    public function publicationV1(): JsonResponse
    {
        $openApi = ($this->factory)(['filter_tag' => 'publication-v1']);

        return new JsonResponse(
            data: $this->serializer->serialize($openApi, 'json'),
            status: 200,
            json: true,
        );
    }

    #[Route('/api/admin/openapi.json', name: 'open_api_admin_json', methods: ['GET'])]
    public function admin(): JsonResponse
    {
        $openApi = ($this->factory)(['filter_tag' => 'admin']);

        return new JsonResponse(
            data: $this->serializer->serialize($openApi, 'json'),
            status: 200,
            json: true,
        );
    }
}
