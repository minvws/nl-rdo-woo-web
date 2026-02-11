<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\EventSubscriber;

use ApiPlatform\Metadata\HttpOperation;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use PublicationApi\Api\Publication\PublicationV1Api;
use PublicationApi\Domain\OpenApi\Exception\ValidatonException;
use PublicationApi\Domain\OpenApi\OpenApiValidationExceptionResponseFactory;
use PublicationApi\Domain\OpenApi\OpenApiValidator;
use PublicationApi\EventSubscriber\PublicationV1ApiRequestResponseValidatorSubscriber;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use function sprintf;

final class PublicationV1ApiRequestResponseValidatorSubscriberTest extends UnitTestCase
{
    private OpenApiValidator&MockInterface $openApiValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->openApiValidator = Mockery::mock(OpenApiValidator::class);
    }

    public function testOnRequestDoesNothingIfValidationIsNotEnabled(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateRequest');

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: false,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest(Mockery::mock(RequestEvent::class));
    }

    public function testOnRequestDoesNothingIfNotMainRequest(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateRequest');

        $requestEvent = Mockery::mock(RequestEvent::class);
        $requestEvent->expects('isMainRequest')
            ->once()
            ->andReturnFalse();

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestDoesNothingOnNonPublicationV1RequestWithoutApiOperationAttribute(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateRequest');

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $requestEvent = new RequestEvent(Mockery::mock(KernelInterface::class), $request, 1);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestDoesNothingOnNonPublicationV1RequestWithInvalidApiOperationAttribute(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateRequest');

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => new stdClass()]);

        $requestEvent = new RequestEvent(Mockery::mock(KernelInterface::class), $request, 1);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestCallsValidation(): void
    {
        $expectedRoutePrefix = '/my-prefix';
        $expectedUriTemplate = '/foobar/{id}';
        $expectedMethod = 'get';

        $httpOperation = Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')->once()->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')->once()->andReturn($expectedUriTemplate);

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->once()
            ->andReturn($expectedMethod);

        $requestEvent = new RequestEvent(Mockery::mock(KernelInterface::class), $request, 1);

        $expectedPath = sprintf('%s%s%s', PublicationV1Api::API_PREFIX, $expectedRoutePrefix, $expectedUriTemplate);
        $this->openApiValidator
            ->shouldReceive('validateRequest')
            ->once()
            ->with($request, $expectedPath, $expectedMethod);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnRequestHandlesValidationException(): void
    {
        $expectedRoutePrefix = '/my-prefix';
        $expectedUriTemplate = '/foobar/{id}';
        $expectedMethod = 'post';

        $httpOperation = Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->once()
            ->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->once()
            ->andReturn($expectedUriTemplate);

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->once()
            ->andReturn($expectedMethod);

        $requestEvent = new RequestEvent(Mockery::mock(KernelInterface::class), $request, 1);

        $expectedPath = sprintf('%s%s%s', PublicationV1Api::API_PREFIX, $expectedRoutePrefix, $expectedUriTemplate);
        $validatonException = new ValidatonException('foo', 0, new Exception());
        $this->openApiValidator
            ->shouldReceive('validateRequest')
            ->once()
            ->with($request, $expectedPath, $expectedMethod)
            ->andThrows($validatonException);

        $openApiValidationExceptionResponseFactory = Mockery::mock(OpenApiValidationExceptionResponseFactory::class);
        $openApiValidationExceptionResponseFactory
            ->expects('buildJsonResponse')
            ->once()
            ->with($validatonException);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            $openApiValidationExceptionResponseFactory,
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onRequest($requestEvent);
    }

    public function testOnResponseDoesNothingIfValidationIsNotEnabled(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $responseEvent = new ResponseEvent(
            Mockery::mock(KernelInterface::class),
            Mockery::mock(Request::class),
            1,
            Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: false,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseDoesNothingIfResponseIsValidatedOnTerminate(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $responseEvent = new ResponseEvent(
            Mockery::mock(KernelInterface::class),
            Mockery::mock(Request::class),
            1,
            Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseDoesNothingIfNotMainRequest(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $responseEvent = new ResponseEvent(
            Mockery::mock(KernelInterface::class),
            Mockery::mock(Request::class),
            HttpKernelInterface::SUB_REQUEST,
            Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseDoesNothingOnNonPublicationV1RequestWithoutAvailableOperation(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $responseEvent = new ResponseEvent(Mockery::mock(KernelInterface::class), $request, 1, Mockery::mock(Response::class));

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnResponseHandlesValidationExceptions(): void
    {
        $expectedRoutePrefix = '/api';
        $expectedUriTemplate = '/foobar/{id}';
        $expectedMethod = 'GET';

        $httpOperation = Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->once()
            ->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->once()
            ->andReturn($expectedUriTemplate);

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request
            ->shouldReceive('getMethod')
            ->once()
            ->andReturn($expectedMethod);

        $response = Mockery::mock(Response::class);

        $validatonException = new ValidatonException('foo', 0, new Exception());

        $expectedPath = sprintf('%s%s%s', PublicationV1Api::API_PREFIX, $expectedRoutePrefix, $expectedUriTemplate);
        $this->openApiValidator
            ->shouldReceive('validateResponse')
            ->once()
            ->with($response, $expectedPath, $expectedMethod)
            ->andThrow($validatonException);

        $openApiPublicationV1ValidationExceptionResponseFactory = Mockery::mock(OpenApiValidationExceptionResponseFactory::class);
        $openApiPublicationV1ValidationExceptionResponseFactory
            ->expects('buildJsonResponse')
            ->once()
            ->with($validatonException);

        $responseEvent = new ResponseEvent(Mockery::mock(KernelInterface::class), $request, 1, $response);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            $openApiPublicationV1ValidationExceptionResponseFactory,
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onResponse($responseEvent);
    }

    public function testOnTerminateDoesNothingIfValidationIsNotEnabled(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $terminateEvent = new TerminateEvent(
            Mockery::mock(KernelInterface::class),
            Mockery::mock(Request::class),
            Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: false,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateDoesNothingIfResponseIsValidatedOnResponse(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $terminateEvent = new TerminateEvent(
            Mockery::mock(KernelInterface::class),
            Mockery::mock(Request::class),
            Mockery::mock(Response::class),
        );

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: false,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateDoesNothingOnNonPublicationV1RequestWithoutAvailableOperation(): void
    {
        $this->assertMockMethodNotCalled($this->openApiValidator, 'validateResponse');

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag();

        $terminateEvent = new TerminateEvent(Mockery::mock(KernelInterface::class), $request, Mockery::mock(Response::class));

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            Mockery::mock(LoggerInterface::class),
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }

    public function testOnTerminateHandlesValidationExceptions(): void
    {
        $expectedRoutePrefix = '/api';
        $expectedUriTemplate = '/foobar/{id}';
        $expectedMethod = 'GET';

        $httpOperation = Mockery::mock(HttpOperation::class);
        $httpOperation->shouldReceive('getRoutePrefix')
            ->twice()
            ->andReturn($expectedRoutePrefix);
        $httpOperation->shouldReceive('getUriTemplate')
            ->twice()
            ->andReturn($expectedUriTemplate);

        $request = Mockery::mock(Request::class);
        $request->attributes = new ParameterBag(['_api_operation' => $httpOperation]);
        $request->shouldReceive('getMethod')
            ->times(2)
            ->andReturn($expectedMethod);

        $response = Mockery::mock(Response::class);

        $exceptionMessage = 'foo';
        $validatonException = new ValidatonException($exceptionMessage, 0, new Exception());

        $expectedPath = sprintf('%s%s%s', PublicationV1Api::API_PREFIX, $expectedRoutePrefix, $expectedUriTemplate);
        $this->openApiValidator
            ->shouldReceive('validateResponse')
            ->once()
            ->with($response, $expectedPath, $expectedMethod)
            ->andThrow($validatonException);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('error')
            ->once()
            ->with('Response validation failed', [
                'exception_class' => $validatonException::class,
                'exception_message' => $exceptionMessage,
                'request_method' => $expectedMethod,
                'request_path' => $expectedPath,
            ]);

        $terminateEvent = new TerminateEvent(Mockery::mock(KernelInterface::class), $request, $response);

        $publicationV1ApiRequestResponseValidatorSubscriber = new PublicationV1ApiRequestResponseValidatorSubscriber(
            $this->openApiValidator,
            Mockery::mock(OpenApiValidationExceptionResponseFactory::class),
            $logger,
            enableRequestValidation: true,
            enableResponseValidation: true,
            validateResponseOnTerminate: true,
        );
        $publicationV1ApiRequestResponseValidatorSubscriber->onTerminate($terminateEvent);
    }
}
