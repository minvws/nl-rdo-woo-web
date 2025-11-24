<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiPostWriteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['postWriteLog', EventPriorities::POST_WRITE],
        ];
    }

    public function postWriteLog(ViewEvent $event): void
    {
        $request = $event->getRequest();

        $method = $request->getMethod();
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return;
        }

        $controllerResult = $event->getControllerResult();
        if (! is_object($controllerResult)) {
            return;
        }

        $this->logger->info('ApiPostWrite handle succesful', [
            'type' => get_debug_type($controllerResult),
            'resource' => $request->attributes->get('_api_resource_class'),
            'method' => $method,
        ]);
    }
}
