<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Api\Publication\V1\PublicationV1Api;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class FeaturePublicationV1ApiSubscriber
{
    public function __construct(
        #[Autowire('%has_feature_publication_v1_api%')]
        private bool $hasFeaturePublicationV1,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onRequest(RequestEvent $event): void
    {
        if ($this->hasFeaturePublicationV1) {
            return;
        }

        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), PublicationV1Api::API_PREFIX)) {
            throw new NotFoundHttpException();
        }
    }
}
