<?php

declare(strict_types=1);

namespace App\Service\Security\ApplicationMode;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will check the application mode and redirect any requests outside the configured mode.
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest')]
readonly class ApplicationModeRedirector
{
    public const string ADMIN_PATH = '/balie';
    public const string API_PATH = '/api';
    public const string PUBLIC_PATH = '/';

    public function __construct(
        private ApplicationMode $applicationMode,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($this->isExcludedFromRedirect($event)) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        $isAdminPath = str_starts_with($path, self::ADMIN_PATH);
        $isApiPath = str_starts_with($path, self::API_PATH);
        $isPublicPath = ! $isAdminPath && ! $isApiPath;

        if ($this->applicationMode->isAdmin() && ! $isAdminPath) {
            $event->setResponse(new RedirectResponse(self::ADMIN_PATH));
        }

        if ($this->applicationMode->isApi() && ! $isApiPath) {
            $event->setResponse(new RedirectResponse(self::API_PATH));
        }

        if ($this->applicationMode->isPublic() && ! $isPublicPath) {
            $event->setResponse(new RedirectResponse(self::PUBLIC_PATH));
        }
    }

    private function isExcludedFromRedirect(RequestEvent $event): bool
    {
        if ($event->getRequest()->attributes->get('_firewall_context') === 'security.firewall.map.context.dev') {
            return true;
        }

        $path = $event->getRequest()->getPathInfo();
        if (str_starts_with($path, '/health')) {
            return true;
        }

        if ($this->applicationMode->isAll()) {
            return true;
        }

        return false;
    }
}
