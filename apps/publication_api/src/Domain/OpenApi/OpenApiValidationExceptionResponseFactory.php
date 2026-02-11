<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use PublicationApi\Api\Publication\PublicationV1Api;
use PublicationApi\Domain\OpenApi\Exception\FormatMismatchException;
use PublicationApi\Domain\OpenApi\Exception\KeywordMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SchemaMismatchException;
use PublicationApi\Domain\OpenApi\Exception\ValidatonException;
use Symfony\Component\HttpFoundation\JsonResponse;

use function sprintf;

class OpenApiValidationExceptionResponseFactory
{
    public function buildJsonResponse(ValidatonException $exception): JsonResponse
    {
        $statusCode = JsonResponse::HTTP_BAD_REQUEST;
        $data = [
            'title' => sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
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
