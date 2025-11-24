<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use Shared\Service\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This listener will check if the user that has logged in needs to change his password. If so, we will automatically
 * redirect to the change password page.
 */
class ChangePasswordSubscriber
{
    /**
     * Skip the redirector when we are on these routes, otherwise we end up in a redirect loop.
     *
     * @var list<string>
     */
    protected array $skipRoutes = [
        '2fa_check',
        '2fa_login',
        'app_admin_user_profile',
    ];

    public function __construct(protected Security $security, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        // Skip non-main requests
        if (! $event->isMainRequest()) {
            return;
        }

        // Skip if we are on the change password page
        if (in_array($event->getRequest()->get('_route'), $this->skipRoutes)) {
            return;
        }

        // Nobody is logged in
        $user = $this->security->getUser();
        if (! $user instanceof User) {
            return;
        }

        if ($user->isPasswordChangeRequired()) {
            // Set target path so we return back to the correct page after changing the password
            $event->getRequest()->getSession()->set('target_path', $event->getRequest()->getRequestUri());
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_admin_user_profile')));
        }
    }
}
