<?php

declare(strict_types=1);

namespace App\Domain\OpenApi;

use App\Api\Publication\V1\PublicationV1Api;
use App\Domain\OpenApi\Exceptions\FormatMismatchException;
use App\Domain\OpenApi\Exceptions\KeywordMismatchException;
use App\Domain\OpenApi\Exceptions\SchemaMismatchException;
use App\Domain\OpenApi\Exceptions\ValidatonException;
use Symfony\Component\HttpFoundation\JsonResponse;

class OpenApiValidationExceptionResponseFactory
{
    public function buildJsonResponse(ValidatonException $exception): JsonResponse
    {
        $statusCode = JsonResponse::HTTP_BAD_REQUEST;
        $data = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => $statusCode,
            'detail' => $exception->getMessage(),
            'instance' => 'response',
        ];

        if ($exception instanceof SchemaMismatchException && $exception->getBreadCrumb() !== null) {
            $data['field'] = $exception->getBreadCrumb();
        }

        if ($exception instanceof KeywordMismatchException) {
            $data['keyword'] = $exception->getKeyword();
        }

        if ($exception instanceof FormatMismatchException) {
            $data['format'] = $exception->getFormat();
        }

        return new JsonResponse($data, $statusCode, ['Content-Type' => 'application/problem+json']);
    }
}
