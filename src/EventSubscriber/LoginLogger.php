<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LoginSuccessEvent::class, method: 'onAuthenticationSuccess')]
#[AsEventListener(event: LoginFailureEvent::class, method: 'onAuthenticationFailure')]
#[AsEventListener(event: LogoutEvent::class, method: 'onLogout')]
readonly class LoginLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private RouterInterface $router,
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        /** @var User|null $user */
        $user = $event->getToken()?->getUser();
        if (! $user) {
            return;
        }

        $this->logger->log('info', 'Logout success', [
            'user_id' => $user->getUserIdentifier(),
        ]);
    }

    public function onAuthenticationSuccess(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        if (! $user) {
            return;
        }

        // If the user has access to the dossier list this should always be used, with a fallback to user admin.
        if ($this->authorizationMatrix->isAuthorized('dossier', 'read')) {
            $landingUrl = $this->router->generate('app_admin_dossiers');
        } else {
            $landingUrl = $this->router->generate('app_admin_users');
        }

        $event->setResponse(new RedirectResponse($landingUrl));
    }

    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $loginName = $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier() ?? 'unknown_user';
        $this->logger->error('Login failure', [
            'exception_message_key' => $exception->getMessageKey(),
            'exception_message_data' => $exception->getMessageData(),
            'login_name' => $loginName,
        ]);
    }
}
