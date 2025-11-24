<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use ApiPlatform\Metadata\HttpOperation;
use Psr\Log\LoggerInterface;
use Shared\Api\Publication\V1\PublicationV1Api;
use Shared\Domain\OpenApi\Exceptions\ValidatonException;
use Shared\Domain\OpenApi\OpenApiValidationExceptionResponseFactory;
use Shared\Domain\OpenApi\OpenApiValidator;
use Shared\EventSubscriber\PublicationV1ApiRequestResponseValidatorSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class PublicationV1ApiRequestResponseValidatorSubscriberTest extends UnitTestCase
{
    public function testOnRequestDoesNothingIfValidationIsNotEnabled(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: false,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest(\Mockery::mock(RequestEvent::class));
    }

    public function testOnRequestDoesNothingIfNotMainRequest(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $requestEvent = \Mockery::mock(RequestEvent::class);
        $requestEvent->expects('isMainRequest')
            ->once()
            ->andReturnFalse();

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestDoesNothingOnNonPublicationV1RequestWithoutApiOperationAttribute(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $requestEvent = new RequestEvent(\Mockery::mock(KernelInterface::class), $request, 1);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestDoesNothingOnNonPublicationV1RequestWithInvalidApiOperationAttribute(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => new \stdClass()]);

        $requestEvent = new RequestEvent(\Mockery::mock(KernelInterface::class), $request, 1);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestDoesNothingOnNonPublicationV1RequestWithInvalidRoutPrefixAndClassAsApiOperationAttribute(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $httpOperation = \Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->once()
            ->andReturn('/invalid/prefix');
        $httpOperation->shouldReceive('getClass')
            ->once()
            ->andReturn(\stdClass::class);

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);

        $requestEvent = new RequestEvent(\Mockery::mock(KernelInterface::class), $request, 1);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestCallsValidation(): void
    {
        $routePrefix = PublicationV1Api::API_PREFIX;
        $uriTemplate = 'foo';
        $method = 'bar';

        $httpOperation = \Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->times(2)
            ->andReturn($routePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->once()
            ->andReturn($uriTemplate);

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);

        $requestEvent = new RequestEvent(\Mockery::mock(KernelInterface::class), $request, 1);

        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $openApiValidator
            ->shouldReceive('validateRequest')
            ->once()
            ->with($request, \sprintf('%s%s', $routePrefix, $uriTemplate), $method);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestHandlesValidationException(): void
    {
        $routePrefix = PublicationV1Api::API_PREFIX;
        $uriTemplate = 'foo';
        $method = 'bar';

        $httpOperation = \Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->times(2)
            ->andReturn($routePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->once()
            ->andReturn($uriTemplate);

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->once()
            ->andReturn($method);

        $requestEvent = new RequestEvent(\Mockery::mock(KernelInterface::class), $request, 1);

        $validatonException = new ValidatonException('foo', 0, new \Exception());
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $openApiValidator->shouldReceive('validateRequest')
            ->once()
            ->with($request, \sprintf('%s%s', $routePrefix, $uriTemplate), $method)
            ->andThrows($validatonException);

        $openApiValidationExceptionResponseFactory = \Mockery::mock(OpenApiValidationExceptionResponseFactory::class);
        $openApiValidationExceptionResponseFactory
            ->expects('buildJsonResponse')
            ->once()
            ->with($validatonException);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            $openApiValidationExceptionResponseFactory,
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnResponseDoesNothingIfValidationIsNotEnabled(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $responseEvent = new ResponseEvent(
            \Mockery::mock(KernelInterface::class),
            \Mockery::mock(Request::class),
            1,
            \Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: false,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseDoesNothingIfResponseIsValidatedOnTerminate(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $responseEvent = new ResponseEvent(
            \Mockery::mock(KernelInterface::class),
            \Mockery::mock(Request::class),
            1,
            \Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseDoesNothingOnNonPublicationV1RequestWithoutAvailableOperation(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $responseEvent = new ResponseEvent(\Mockery::mock(KernelInterface::class), $request, 1, \Mockery::mock(Response::class));

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseHandlesValidationExceptions(): void
    {
        $expectedRoutePrefix = \sprintf('%s/%s', PublicationV1Api::API_PREFIX, '/foobar');
        $expectedUriTemplate = '/api/some/endpoint/{id}';

        $httpOperation = \Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->times(2)
            ->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->once()
            ->andReturn($expectedUriTemplate);

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($expectedMethod = 'GET');

        $response = \Mockery::mock(Response::class);

        $validatonException = new ValidatonException('foo', 0, new \Exception());

        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $openApiValidator
            ->shouldReceive('validateResponse')
            ->once()
            ->with($response, sprintf('%s%s', $expectedRoutePrefix, $expectedUriTemplate), $expectedMethod)
            ->andThrow($validatonException);

        $openApiPublicationV1ValidationExceptionResponseFactory = \Mockery::mock(OpenApiValidationExceptionResponseFactory::class);
        $openApiPublicationV1ValidationExceptionResponseFactory
            ->expects('buildJsonResponse')
            ->once()
            ->with($validatonException);

        $responseEvent = new ResponseEvent(\Mockery::mock(KernelInterface::class), $request, 1, $response);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            $openApiPublicationV1ValidationExceptionResponseFactory,
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnTerminateDoesNothingIfValidationIsNotEnabled(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $terminateEvent = new TerminateEvent(
            \Mockery::mock(KernelInterface::class),
            \Mockery::mock(Request::class),
            \Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: false,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateDoesNothingIfResponseIsValidatedOnResponse(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $terminateEvent = new TerminateEvent(
            \Mockery::mock(KernelInterface::class),
            \Mockery::mock(Request::class),
            \Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateDoesNothingOnNonPublicationV1RequestWithoutAvailableOperation(): void
    {
        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $this->assertMockMethodNotCalled($openApiValidator, 'validateRequest');

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $terminateEvent = new TerminateEvent(\Mockery::mock(KernelInterface::class), $request, \Mockery::mock(Response::class));

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            \Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateHandlesValidationExceptions(): void
    {
        $expectedRoutePrefix = \sprintf('%s/%s', PublicationV1Api::API_PREFIX, '/foobar');
        $expectedUriTemplate = '/api/some/endpoint/{id}';
        $expectedMethod = 'foo';

        $httpOperation = \Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->times(3)
            ->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->times(2)
            ->andReturn($expectedUriTemplate);

        $request = \Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->times(2)
            ->andReturn($expectedMethod);

        $response = \Mockery::mock(Response::class);

        $exceptionMessage = 'foo';
        $validatonException = new ValidatonException($exceptionMessage, 0, new \Exception());

        $openApiValidator = \Mockery::mock(OpenApiValidator::class);
        $openApiValidator
            ->shouldReceive('validateResponse')
            ->once()
            ->with($response, sprintf('%s%s', $expectedRoutePrefix, $expectedUriTemplate), $expectedMethod)
            ->andThrow($validatonException);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->with('Response validation failed', [
                'exception_class' => $validatonException::class,
                'exception_message' => $exceptionMessage,
                'request_method' => $expectedMethod,
                'request_path' => sprintf('%s%s', $expectedRoutePrefix, $expectedUriTemplate),
            ]);

        $terminateEvent = new TerminateEvent(\Mockery::mock(KernelInterface::class), $request, $response);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $openApiValidator,
            \Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            $logger,
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }
}
