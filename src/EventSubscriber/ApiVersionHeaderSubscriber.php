<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, method: 'addVersionHeader', priority: EventPriorities::PRE_SERIALIZE)]
readonly class ApiVersionHeaderSubscriber
{
    public function __construct(
        private string $apiVersion,
    ) {
    }

    public function addVersionHeader(ResponseEvent $event): void
    {
        $response = $event->getResponse()->headers;
        $response->add([
            'API-Version' => $this->apiVersion,
        ]);
    }
}
