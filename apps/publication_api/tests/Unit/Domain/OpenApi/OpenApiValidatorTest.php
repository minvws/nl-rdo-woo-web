<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Domain\OpenApi;

use cebe\openapi\spec\OpenApi;
use Exception;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\OperationAddress;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\RoutedServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use League\OpenAPIValidation\Schema\Exception\FormatMismatch;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PublicationApi\Domain\OpenApi\Exception\FormatMismatchException;
use PublicationApi\Domain\OpenApi\Exception\KeywordMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SchemaMismatchException;
use PublicationApi\Domain\OpenApi\Exception\SpecException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use PublicationApi\Domain\OpenApi\OpenApiSpecGenerator;
use PublicationApi\Domain\OpenApi\OpenApiValidator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

class OpenApiValidatorTest extends UnitTestCase
{
    public function testValidateRequest(): void
    {
        $request = new Request();
        $path = 'foo';
        $method = 'bar';

        $psrRequest = Mockery::mock(ServerRequestInterface::class);
        $openApi = Mockery::mock(OpenApi::class);

        $routedRequestValidator = Mockery::mock(RoutedServerRequestValidator::class);
        $routedRequestValidator->expects('validate')
            ->with(
                Mockery::on(static function (OperationAddress $operationAddress) use ($path, $method): bool {
                    return $operationAddress->path() === sprintf('/%s', $path) && $operationAddress->method() === $method;
                }),
                $psrRequest,
            );

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);
        $validatorBuilder->expects('fromSchema')
            ->with($openApi)
            ->andReturn($validatorBuilder);
        $validatorBuilder->expects('getRoutedRequestValidator')
            ->andReturn($routedRequestValidator);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createRequest')
            ->with($request)
            ->andReturn($psrRequest);

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
            ->expects('getSpec')
            ->andReturn($openApi);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);
        $openApiValidator->validateRequest($request, $path, $method);
    }

    /**
     * @param class-string<ValidationException> $expectedOpenApiPublicationV1ValidatonException
     */
    #[DataProvider('validatonExceptionDataProvider')]
    public function testValidateRequestThrowsCorrectFormatMismatchException(
        Exception $validationException,
        string $expectedOpenApiPublicationV1ValidatonException,
    ): void {
        $request = new Request();

        $psrRequest = Mockery::mock(ServerRequestInterface::class);
        $openApi = Mockery::mock(OpenApi::class);

        $routedRequestValidator = Mockery::mock(RoutedServerRequestValidator::class);
        $routedRequestValidator->expects('validate')
            ->andThrow($validationException);

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);
        $validatorBuilder->expects('fromSchema')
            ->with($openApi)
            ->andReturn($validatorBuilder);
        $validatorBuilder->expects('getRoutedRequestValidator')
            ->andReturn($routedRequestValidator);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createRequest')
            ->with($request)
            ->andReturn($psrRequest);

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
            ->expects('getSpec')
            ->andReturn($openApi);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);

        self::expectException($expectedOpenApiPublicationV1ValidatonException);
        $openApiValidator->validateRequest($request, 'foo', 'bar');
    }

    public function testValidateRequestWithSpecException(): void
    {
        $request = new Request();
        $specException = new SpecException();

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createRequest')
            ->with($request)
            ->andReturn(Mockery::mock(ServerRequestInterface::class));

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
        ->expects('getSpec')
            ->andThrow($specException);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);

        self::expectException($specException::class);
        $openApiValidator->validateRequest($request, 'foo', 'bar');
    }

    public function testValidateResponse(): void
    {
        $response = new Response();
        $path = 'foo';
        $method = 'bar';

        $psrResponse = Mockery::mock(ResponseInterface::class);
        $openApi = Mockery::mock(OpenApi::class);

        $responseValidator = Mockery::mock(ResponseValidator::class);
        $responseValidator->expects('validate')
            ->with(
                Mockery::on(static function (OperationAddress $operationAddress) use ($path, $method): bool {
                    return $operationAddress->path() === sprintf('/%s', $path) && $operationAddress->method() === $method;
                }),
                $psrResponse,
            );

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);
        $validatorBuilder->expects('fromSchema')
            ->with($openApi)
            ->andReturn($validatorBuilder);
        $validatorBuilder->expects('getResponseValidator')
            ->andReturn($responseValidator);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createResponse')
            ->with($response)
            ->andReturn($psrResponse);

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
            ->expects('getSpec')
            ->andReturn($openApi);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);
        $openApiValidator->validateResponse($response, $path, $method);
    }

    /**
     * @param class-string<ValidationException> $expectedValidatonException
     */
    #[DataProvider('validatonExceptionDataProvider')]
    public function testValidateResponseThrowsCorrectFormatMismatchException(
        Exception $validationException,
        string $expectedValidatonException,
    ): void {
        $response = new Response();

        $psrResponse = Mockery::mock(ResponseInterface::class);
        $openApi = Mockery::mock(OpenApi::class);

        $responseValidator = Mockery::mock(ResponseValidator::class);
        $responseValidator->expects('validate')
            ->andThrow($validationException);

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);
        $validatorBuilder->expects('fromSchema')
            ->with($openApi)
            ->andReturn($validatorBuilder);
        $validatorBuilder->expects('getResponseValidator')
            ->andReturn($responseValidator);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createResponse')
            ->with($response)
            ->andReturn($psrResponse);

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
            ->expects('getSpec')
            ->andReturn($openApi);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);

        self::expectException($expectedValidatonException);
        $openApiValidator->validateResponse($response, 'foo', 'bar');
    }

    public function testValidateResponseWithSpecException(): void
    {
        $response = new Response();
        $specException = new SpecException();

        $validatorBuilder = Mockery::mock(ValidatorBuilder::class);

        $psrHttpFactory = Mockery::mock(PsrHttpFactory::class);
        $psrHttpFactory->expects('createResponse')
            ->with($response)
            ->andReturn(Mockery::mock(ResponseInterface::class));

        $openApiSpecGenerator = Mockery::mock(OpenApiSpecGenerator::class);
        $openApiSpecGenerator
            ->expects('getSpec')
            ->andThrow($specException);

        $openApiValidator = new OpenApiValidator($validatorBuilder, $psrHttpFactory, $openApiSpecGenerator);

        self::expectException($specException::class);
        $openApiValidator->validateResponse($response, 'foo', 'bar');
    }

    /**
     * @return array<string, array{Exception, class-string<Exception>}>
     */
    public static function validatonExceptionDataProvider(): array
    {
        return [
            'format mismatch' => [
                FormatMismatch::fromFormat('foo', 'bar', 'baz'),
                FormatMismatchException::class,
            ],
            'keyword mismatch' => [
                KeywordMismatch::fromKeyword('foo', 'bar'),
                KeywordMismatchException::class,
            ],
            'schema mismatch' => [
                new SchemaMismatch(),
                SchemaMismatchException::class,
            ],
            'validation failed' => [
                new ValidationFailed(),
                ValidationException::class,
            ],
            'validation failed wrapping format mismatch' => [
                new ValidationFailed('wrapped', 0, FormatMismatch::fromFormat('foo', 'bar', 'baz')),
                FormatMismatchException::class,
            ],
            'validation failed wrapping keyword mismatch' => [
                new ValidationFailed('wrapped', 0, KeywordMismatch::fromKeyword('foo', 'bar')),
                KeywordMismatchException::class,
            ],
            'validation failed wrapping schema mismatch' => [
                new ValidationFailed('wrapped', 0, new SchemaMismatch()),
                SchemaMismatchException::class,
            ],
            'other exception' => [
                new Exception(),
                Exception::class,
            ],
        ];
    }
}
