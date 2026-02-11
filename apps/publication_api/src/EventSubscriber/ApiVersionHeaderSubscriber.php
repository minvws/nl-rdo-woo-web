<?php

declare(strict_types=1);

namespace PublicationApi\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class ApiVersionHeaderSubscriber
{
    public const string HEADER_NAME = 'API-Version';

    public function __construct(
        #[Autowire(param: 'api_platform.version')]
        private string $apiVersion,
    ) {
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: EventPriorities::PRE_SERIALIZE)]
    public function addVersionHeader(ResponseEvent $event): void
    {
        $response = $event->getResponse()->headers;
        $response->add([
            self::HEADER_NAME => $this->apiVersion,
        ]);
    }
}
