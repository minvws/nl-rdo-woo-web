<?php

declare(strict_types=1);

namespace PublicationApi\EventSubscriber;

use ApiPlatform\Metadata\HttpOperation;
use Psr\Log\LoggerInterface;
use PublicationApi\Api\Publication\PublicationV1Api;
use PublicationApi\Domain\OpenApi\Exception\ValidatonException;
use PublicationApi\Domain\OpenApi\OpenApiValidationExceptionResponseFactory;
use PublicationApi\Domain\OpenApi\OpenApiValidator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Webmozart\Assert\Assert;

use function sprintf;

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
        if (! $this->hasOperation($request)) {
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

        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! $this->hasOperation($request)) {
            return;
        }

        try {
            $this->openApiValidator->validateResponse($event->getResponse(), $this->getAbsolutePath($request), $request->getMethod());
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
        if (! $this->hasOperation($request)) {
            return;
        }

        try {
            $this->openApiValidator->validateResponse($event->getResponse(), $this->getAbsolutePath($request), $request->getMethod());
        } catch (ValidatonException $e) {
            $this->logger->error('Response validation failed', [
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'request_method' => $request->getMethod(),
                'request_path' => $this->getAbsolutePath($request),
            ]);
        }
    }

    private function getAbsolutePath(Request $request): string
    {
        $operation = $this->getOperation($request);

        return sprintf(
            '%s%s%s',
            PublicationV1Api::API_PREFIX,
            $operation->getRoutePrefix(),
            $operation->getUriTemplate(),
        );
    }

    private function hasOperation(Request $request): bool
    {
        $operation = $request->attributes->get('_api_previous_operation')
            ?? $request->attributes->get('_api_requested_operation')
            ?? $request->attributes->get('_api_operation');

        return $operation instanceof HttpOperation;
    }

    private function getOperation(Request $request): HttpOperation
    {
        $operation = $request->attributes->get('_api_previous_operation')
            ?? $request->attributes->get('_api_requested_operation')
            ?? $request->attributes->get('_api_operation');

        Assert::isInstanceOf($operation, HttpOperation::class);

        return $operation;
    }
}
