<?php

declare(strict_types=1);

namespace Shared\Domain\OpenApi;

use Shared\Api\Publication\V1\PublicationV1Api;
use Shared\Domain\OpenApi\Exceptions\FormatMismatchException;
use Shared\Domain\OpenApi\Exceptions\KeywordMismatchException;
use Shared\Domain\OpenApi\Exceptions\SchemaMismatchException;
use Shared\Domain\OpenApi\Exceptions\ValidatonException;
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
