<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * This listener will log successful and failed login attempts.
 */
class LoginLoggerSubscriber implements EventSubscriberInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $this->logger->log('info', 'Login success', [
            'user_id' => $user->getUserIdentifier(),
        ]);
    }

    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $loginName = $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier() ?? 'unknown_user';

        $this->logger->error('Login failure', [
            'exception' => $exception->getMessage(),
            'login_name' => $loginName,
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessEvent::class => 'onAuthenticationSuccess',
            LoginFailureEvent::class => 'onAuthenticationFailure',
        ];
    }
}
