<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\Model\Response;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiCommonResponsesProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiResponsesComponentProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiSchemasComponentProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OperationResponseDefinition;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use function in_array;
use function strtoupper;

final readonly class CommonResponses implements OpenApiSchemasComponentProvider, OpenApiCommonResponsesProvider, OpenApiResponsesComponentProvider
{
    public function getCommonResponses(): array
    {
        return [
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_BAD_REQUEST, // 400
                response: new Reference('#/components/responses/BadRequestResponse'),
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_UNAUTHORIZED, // 401
                response: new Reference('#/components/responses/UnauthorizedResponse'),
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_FORBIDDEN, // 403
                response: new Reference('#/components/responses/ForbiddenResponse'),
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_NOT_FOUND, // 404
                response: new Reference('#/components/responses/NotFoundResponse'),
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_METHOD_NOT_ALLOWED, // 405
                response: new Reference('#/components/responses/MethodNotAllowedResponse'),
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY, // 422
                response: new Reference('#/components/responses/UnprocessableEntityResponse'),
                when: static function (Operation $operation, string $path, string $httpMethod): bool {
                    return in_array(strtoupper($httpMethod), ['POST', 'PUT', 'PATCH'], true);
                },
            ),
            new OperationResponseDefinition(
                statusCode: SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR, // 500
                response: new Reference('#/components/responses/ServerErrorResponse'),
            ),
        ];
    }

    public function getResponses(): array
    {
        return [
            // 400
            'BadRequestResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_BAD_REQUEST],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 401
            'UnauthorizedResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_UNAUTHORIZED],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 403
            'ForbiddenResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_FORBIDDEN],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 404
            'NotFoundResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_NOT_FOUND],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 405
            'MethodNotAllowedResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_METHOD_NOT_ALLOWED],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 422
            'ValidationFailedResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
            // 500
            'ServerErrorResponse' => new Response(
                description: SymfonyResponse::$statusTexts[SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR],
                content: new ArrayObject([
                    'application/problem+json' => new MediaType(
                        schema: new ArrayObject(['$ref' => '#/components/schemas/ProblemDetails']),
                    ),
                ]),
            ),
        ];
    }

    public function getSchemas(): array
    {
        return [
            'ProblemDetails' => [
                'type' => 'object',
                'description' => 'RFC 9457 Problem Details',
                'required' => ['type', 'title', 'status'],
                'properties' => [
                    'type' => [
                        'description' => 'A URI reference that identifies the problem type.',
                        'type' => 'string',
                        'format' => 'uri',
                        'readOnly' => true,
                    ],
                    'title' => [
                        'description' => 'A short, human-readable summary of the problem.',
                        'type' => 'string',
                        'readOnly' => true,
                    ],
                    'status' => [
                        'description' => 'The HTTP status code generated by the origin server for this occurrence of the problem.',
                        'type' => 'integer',
                        'readOnly' => true,
                    ],
                    'detail' => [
                        'description' => 'A human-readable explanation specific to this occurrence of the problem.',
                        'type' => 'string',
                        'readOnly' => true,
                    ],
                    'instance' => [
                        'description' => 'A URI reference that identifies the specific occurrence of the problem. It '
                            . 'may or may not yield further information if dereferenced.',
                        'type' => 'string',
                        'format' => 'uri',
                        'readOnly' => true,
                    ],
                ],
            ],
        ];
    }
}
