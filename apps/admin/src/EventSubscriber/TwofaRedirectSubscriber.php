<?php

declare(strict_types=1);

namespace Admin\EventSubscriber;

use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * This listener will check if we have a TwoFactorToken in the TokenStorage. If so, we are in the progress
 * of logging in and need to redirect to the 2fa login page. However, the default way to do this, is not
 * working for us, because we are actually redirecting to a public access page (app_home), which causes all
 * kind of issues like being logged in as a user, but with the wrong token/roles. So we need to do this
 * manually.
 *
 * We check if we are not on the 2fa pages already. If not, we check if the current security token storage
 * has a twofactortoken. If so, we redirect to the 2fa login page. This will always force us to the 2fa page
 * when we are in the middle of authentication.
 */
class TwofaRedirectSubscriber
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
        protected UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        // Don't redirect when we are already on the 2fa pages
        if ($event->getRequest()->attributes->get('_route') === '2fa_login') {
            return;
        }
        if ($event->getRequest()->attributes->get('_route') === '2fa_check') {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token instanceof TwoFactorToken) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('2fa_login')));
        }
    }
}
