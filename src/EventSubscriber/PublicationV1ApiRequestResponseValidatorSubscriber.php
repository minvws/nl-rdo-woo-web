<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use ApiPlatform\Metadata\HttpOperation;
use Psr\Log\LoggerInterface;
use Shared\Api\Publication\V1\PublicationV1Api;
use Shared\Domain\OpenApi\Exceptions\ValidatonException;
use Shared\Domain\OpenApi\OpenApiValidationExceptionResponseFactory;
use Shared\Domain\OpenApi\OpenApiValidator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

final readonly class PublicationV1ApiRequestResponseValidatorSubscriber
{
    public function __construct(
        private OpenApiValidator $openApiValidator,
        private OpenApiValidationExceptionResponseFactory $openApiValidationExceptionResponseFactory,
        private LoggerInterface $logger,
        private bool $enableRequestValidation = true,
        private bool $enableResponseValidation = true,
        private bool $validateResponseOnTerminate = true,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onRequest(RequestEvent $event): void
    {
        if (! $this->enableRequestValidation) {
            return;
        }

        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->isPublicationV1Request($request)) {
            return;
        }

        try {
            $this->openApiValidator->validateRequest($request, $this->getAbsolutePath($request), $request->getMethod());
        } catch (ValidatonException $exception) {
            $event->setResponse($this->openApiValidationExceptionResponseFactory->buildJsonResponse($exception));
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        if (! $this->enableResponseValidation) {
            return;
        }

        if ($this->validateResponseOnTerminate) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->isPublicationV1Request($request)) {
            return;
        }

        $response = $event->getResponse();

        try {
            $this->openApiValidator->validateResponse($response, $this->getAbsolutePath($request), $request->getMethod());
        } catch (ValidatonException $exception) {
            $event->setResponse($this->openApiValidationExceptionResponseFactory->buildJsonResponse($exception));
        }
    }

    #[AsEventListener(event: KernelEvents::TERMINATE)]
    public function onTerminate(TerminateEvent $event): void
    {
        if (! $this->enableResponseValidation) {
            return;
        }

        if (! $this->validateResponseOnTerminate) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->isPublicationV1Request($request)) {
            return;
        }

        $response = $event->getResponse();

        try {
            $this->openApiValidator->validateResponse($response, $this->getAbsolutePath($request), $request->getMethod());
        } catch (ValidatonException $e) {
            $this->logger->error('Response validation failed', [
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'request_method' => $request->getMethod(),
                'request_path' => $this->getAbsolutePath($request),
            ]);
        }
    }

    private function isPublicationV1Request(Request $request): bool
    {
        $operation = $this->getOperation($request);

        if (! $operation instanceof HttpOperation) {
            return false;
        }

        if (str_starts_with($operation->getRoutePrefix() ?? '', PublicationV1Api::API_PREFIX)) {
            return true;
        }

        if (str_starts_with($operation->getClass() ?? '', PublicationV1Api::API_NAMESPACE_PREFIX)) {
            return true;
        }

        return false;
    }

    private function getAbsolutePath(Request $request): string
    {
        $operation = $this->getOperation($request);
        Assert::isInstanceOf($operation, HttpOperation::class);

        return sprintf('%s%s', $operation->getRoutePrefix(), $operation->getUriTemplate());
    }

    private function getOperation(Request $request): ?HttpOperation
    {
        $operation = $request->attributes->get('_api_operation');

        if (! $operation instanceof HttpOperation) {
            return null;
        }

        return $operation;
    }
}
