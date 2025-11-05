<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Api;

use App\Api\Publication\V1\PublicationV1Api;
use App\Domain\OpenApi\Exceptions\FormatMismatchException;
use App\Domain\OpenApi\Exceptions\KeywordMismatchException;
use App\Domain\OpenApi\Exceptions\SchemaMismatchException;
use App\Domain\OpenApi\Exceptions\ValidatonException;
use App\Domain\OpenApi\OpenApiValidationExceptionResponseFactory;
use App\Tests\Unit\UnitTestCase;
use League\OpenAPIValidation\Schema\BreadCrumb;
use League\OpenAPIValidation\Schema\Exception\FormatMismatch;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webmozart\Assert\Assert;

class ValidationExceptionResponseFactoryTest extends UnitTestCase
{
    public function testBuildJsonResponseFromValidationException(): void
    {
        $message = 'message';

        $openApiValidationExceptionResponseFactory = new OpenApiValidationExceptionResponseFactory();
        $response = $openApiValidationExceptionResponseFactory->buildJsonResponse(new ValidatonException($message, 1, new \Exception()));

        $expectedResponseData = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'detail' => $message,
            'instance' => 'response',
        ];

        $this->assertJsonResponse($expectedResponseData, $response);
    }

    public function testBuildJsonResponseFromSchemaMismatchException(): void
    {
        $message = 'message';

        $schemaMismatch = new SchemaMismatch($message);

        $openApiValidationExceptionResponseFactory = new OpenApiValidationExceptionResponseFactory();
        $response = $openApiValidationExceptionResponseFactory->buildJsonResponse(
            SchemaMismatchException::fromSchemaMismatch($schemaMismatch),
        );

        $expectedResponseData = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'detail' => $message,
            'instance' => 'response',
        ];

        $this->assertJsonResponse($expectedResponseData, $response);
    }

    public function testBuildJsonResponseFromSchemaMismatchExceptionWithBreadcrumb(): void
    {
        $message = 'message';
        $compoundIndex = 'compound';

        $schemaMismatch = new SchemaMismatch($message);
        $schemaMismatch->withBreadCrumb(new BreadCrumb($compoundIndex));

        $openApiValidationExceptionResponseFactory = new OpenApiValidationExceptionResponseFactory();
        $response = $openApiValidationExceptionResponseFactory->buildJsonResponse(
            SchemaMismatchException::fromSchemaMismatch($schemaMismatch),
        );

        $expectedResponseData = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'detail' => $message,
            'instance' => 'response',
            'field' => $compoundIndex,
        ];

        $this->assertJsonResponse($expectedResponseData, $response);
    }

    public function testBuildJsonResponseFromKeywordMismatch(): void
    {
        $message = 'message';
        $keyword = 'keyword';

        $openApiValidationExceptionResponseFactory = new OpenApiValidationExceptionResponseFactory();
        $response = $openApiValidationExceptionResponseFactory->buildJsonResponse(
            KeywordMismatchException::fromKeywordMismatch(KeywordMismatch::fromKeyword($keyword, [], $message)),
        );

        $expectedResponseData = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'detail' => \sprintf('Keyword validation failed: %s', $message),
            'instance' => 'response',
            'keyword' => $keyword,
        ];

        $this->assertJsonResponse($expectedResponseData, $response);
    }

    public function testBuildJsonResponseFromFormatMismatch(): void
    {
        $format = 'format';
        $value = 'value';
        $type = 'type';

        $openApiValidationExceptionResponseFactory = new OpenApiValidationExceptionResponseFactory();
        $response = $openApiValidationExceptionResponseFactory->buildJsonResponse(
            FormatMismatchException::fromFormatMismatch(FormatMismatch::fromFormat($format, $value, $type)),
        );

        $expectedResponseData = [
            'title' => \sprintf('Invalid %s API request', PublicationV1Api::API_NAME),
            'status' => JsonResponse::HTTP_BAD_REQUEST,
            'detail' => \sprintf("Value '%s' does not match format %s of type %s", $value, $format, $type),
            'instance' => 'response',
            'format' => $format,
        ];

        $this->assertJsonResponse($expectedResponseData, $response);
    }

    /**
     * @param array<string, int|string> $expectedResponseData
     */
    private function assertJsonResponse(array $expectedResponseData, JsonResponse $response): void
    {
        $expectedJsonResponse = \json_encode($expectedResponseData);
        Assert::string($expectedJsonResponse);

        $responseContent = $response->getContent();
        Assert::string($responseContent);

        self::assertJsonStringEqualsJsonString($expectedJsonResponse, $responseContent);
    }
}
