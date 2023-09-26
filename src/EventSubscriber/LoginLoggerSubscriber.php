<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * This listener will log successful and failed login attempts.
 */
class LoginLoggerSubscriber implements EventSubscriberInterface
{
    protected LoggerInterface $logger;
    protected AuditLogger $auditLogger;

    public function __construct(LoggerInterface $logger, AuditLogger $auditLogger)
    {
        $this->logger = $logger;
        $this->auditLogger = $auditLogger;
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

        $this->auditLogger->log((new UserLogoutLogEvent())
            ->asExecute()
            ->withActor($user)
            ->withSource('woo'));
    }

    public function onAuthenticationSuccess(LoginSuccessEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        if (! $user) {
            return;
        }

        $this->logger->log('info', 'Login success', [
            'user_id' => $user->getUserIdentifier(),
        ]);

        $this->auditLogger->log((new UserLoginLogEvent())
            ->asExecute()
            ->withActor($user)
            ->withSource('woo')
            ->withData([
                'user_id' => $user->getUserIdentifier(),
                'user_name' => $user->getName(),
                'user_roles' => $user->getRoles(),
            ]));
    }

    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();
        $loginName = $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier() ?? 'unknown_user';

        $this->logger->error('Login failure', [
            'exception' => $exception->getMessage(),
            'login_name' => $loginName,
        ]);

        try {
            $event->getPassport()?->getUser();
            $emailInvalid = false;
        } catch (\Exception) {
            $emailInvalid = true;
        }

        $pwhash = substr(hash('sha256', $event->getPassport()?->getBadge(PasswordCredentials::class)?->getPassword() ?? ''), 0, 16);

        $this->auditLogger->log((new UserLoginLogEvent())
            ->asExecute()
            ->withSource('woo')
            ->withFailed(true, $emailInvalid ? 'invalid_email' : 'invalid_password')
            ->withData([
                'user_id' => $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier(),
                'partial_password_hash' => $pwhash,
            ]));
    }

    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessEvent::class => 'onAuthenticationSuccess',
            LoginFailureEvent::class => 'onAuthenticationFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }
}
