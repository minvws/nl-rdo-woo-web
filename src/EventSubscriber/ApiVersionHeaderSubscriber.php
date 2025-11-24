<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ApiVersionHeaderSubscriber
{
    public function __construct(
        private string $apiVersion,
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: EventPriorities::PRE_SERIALIZE)]
    public function addVersionHeader(ResponseEvent $event): void
    {
        $response = $event->getResponse()->headers;
        $response->add([
            'API-Version' => $this->apiVersion,
        ]);
    }
}
