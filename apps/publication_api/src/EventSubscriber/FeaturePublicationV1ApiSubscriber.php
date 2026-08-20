<?php

declare(strict_types=1);

namespace PublicationApi\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsEventListener(priority: 5)]
final readonly class FeaturePublicationV1ApiSubscriber
{
    public function __construct(
        #[Autowire(param: 'has_feature_publication_v1_api')]
        private bool $hasFeaturePublicationV1,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($this->hasFeaturePublicationV1) {
            return;
        }

        throw new NotFoundHttpException();
    }
}
