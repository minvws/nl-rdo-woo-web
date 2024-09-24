<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will check the APP_MODE environment, and will make sure we can only access the application needed.
 *
 * APP_MODE:
 *          BALIE:      only urls starting with /balie are accessible. Everything else is redirected to /balie
 *          FRONTEND:   urls starting with /balie are not allowed and are redirected to /
 *          BOTH:       no checks are done. Everything is allowed
 */
class AppModeListener implements EventSubscriberInterface
{
    private const BALIE_PATH = '/balie';

    protected string $appMode;
    protected const APP_MODES = ['BALIE', 'FRONTEND', 'BOTH'];

    public function __construct(string $appMode)
    {
        $this->appMode = strtoupper($appMode);

        if (! in_array($this->appMode, self::APP_MODES)) {
            throw new \InvalidArgumentException('Invalid APP_MODE environment variable. Valid values are: BALIE, FRONTEND, BOTH');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // If we are inside the dev firewall, we don't need to check anything
        $firewallName = $event->getRequest()->attributes->get('_firewall_context');
        if ($firewallName == 'security.firewall.map.context.dev') {
            return;
        }

        // Health check can be accessed by all modes
        if (str_starts_with($event->getRequest()->getPathInfo(), '/health')) {
            return;
        }

        // Only /balie urls are allowed in the balie mode
        if ($this->appMode === 'BALIE' && ! str_starts_with($event->getRequest()->getPathInfo(), self::BALIE_PATH)) {
            $event->setResponse(new RedirectResponse(self::BALIE_PATH));
        }

        // Only non-balie urls are allowed in the frontend mode
        if ($this->appMode === 'FRONTEND' && str_starts_with($event->getRequest()->getPathInfo(), self::BALIE_PATH)) {
            $event->setResponse(new RedirectResponse('/'));
        }

        // If we are in BOTH mode, we don't need to do anything
    }
}
