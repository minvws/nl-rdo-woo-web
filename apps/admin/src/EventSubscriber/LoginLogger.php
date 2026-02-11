<?php

declare(strict_types=1);

namespace Admin\EventSubscriber;

use Admin\Domain\Authentication\UserRouteHelper;
use Psr\Log\LoggerInterface;
use Shared\Service\Security\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

readonly class LoginLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private RouterInterface $router,
        private UserRouteHelper $userRouteHelper,
    ) {
    }

    #[AsEventListener(event: LogoutEvent::class)]
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

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onAuthenticationSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (! $user instanceof User) {
            return;
        }

        $landingUrl = $this->router->generate($this->userRouteHelper->getDefaultIndexRouteName());

        $event->setResponse(new RedirectResponse($landingUrl));
    }

    #[AsEventListener(event: LoginFailureEvent::class)]
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
