<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Service\Security\Authorization\AuthorizationEntryRequestStore;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener ensures all admin endpoints check an AuthMatrix permission, except whitelisted urls.
 */
readonly class AuthMatrixEnsureSubscriber
{
    public function __construct(
        private AuthorizationEntryRequestStore $entryStore,
    ) {
    }

    #[AsEventListener(event: KernelEvents::CONTROLLER_ARGUMENTS, priority: -10)]
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $uri = $event->getRequest()->getRequestUri();

        // Only admin routes are required have an AuthMatrix check
        if (! str_starts_with($uri, '/balie')) {
            return;
        }

        // Admin API routes cannot be checked here, as they execute is_granted only after this event
        if (str_starts_with($uri, '/balie/api/')) {
            return;
        }

        // These admin routes are allowed to not have an AuthMatrix check
        $allowedRoutes = [
            '/balie',
            '/balie/',
            '/balie/admin',
            '/balie/2fa',
            '/balie/2fa_check',
            '/balie/contact',
            '/balie/login',
            '/balie/logout',
            '/balie/privacy',
            '/balie/profiel',
            '/balie/toegankelijkheid',
            '/balie/api',
            '/balie/upload',
        ];
        if (in_array($uri, $allowedRoutes)) {
            return;
        }

        if (count($this->entryStore->getEntries()) === 0) {
            throw new AccessDeniedHttpException('Please add an isGranted attribute using an AuthMatrixPermission on the controller.');
        }
    }
}
