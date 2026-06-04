<?php

declare(strict_types=1);

namespace PublicationApi\EventSubscriber;

use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ProblemDetailsExceptionSubscriber
{
    public function __construct(
        private ProblemDetailsFactory $problemDetailsFactory,
    ) {
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
    public function onException(ExceptionEvent $event): void
    {
        $problemDetails = $this->problemDetailsFactory->build($event->getThrowable());
        if ($problemDetails === null) {
            return;
        }

        $event->setResponse(new JsonResponse(
            $problemDetails,
            $problemDetails->status,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
