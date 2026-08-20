<?php

declare(strict_types=1);

namespace PublicationApi\EventSubscriber;

use PublicationApi\Domain\OpenApi\ProblemDetailsFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

#[AsEventListener(priority: -10)]
final readonly class ProblemDetailsExceptionSubscriber
{
    public function __construct(
        private ProblemDetailsFactory $problemDetailsFactory,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
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
