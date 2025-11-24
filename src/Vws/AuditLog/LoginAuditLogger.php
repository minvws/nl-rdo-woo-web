<?php

declare(strict_types=1);

namespace Shared\Vws\AuditLog;

use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use Shared\Service\Security\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class, method: 'onLogout')]
#[AsEventListener(event: LoginFailureEvent::class, method: 'onAuthenticationFailure')]
readonly class LoginAuditLogger
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        /** @var User|null $user */
        $user = $event->getToken()?->getUser();
        if (! $user) {
            return;
        }

        $this->auditLogger->log((new UserLogoutLogEvent())
            ->asExecute()
            ->withActor($user)
            ->withSource('woo'));
    }

    public function onAuthenticationFailure(LoginFailureEvent $event): void
    {
        $exception = $event->getException();

        try {
            $event->getPassport()?->getUser();
            $emailInvalid = false;
        } catch (\Exception) {
            $emailInvalid = true;
        }

        $partialPasswordHash = substr(hash('sha256', $event->getPassport()?->getBadge(PasswordCredentials::class)?->getPassword() ?? ''), 0, 16);

        $this->auditLogger->log((new UserLoginLogEvent())
            ->asExecute()
            ->withSource('woo')
            ->withFailed(true, $emailInvalid ? 'invalid_email' : 'invalid_password')
            ->withData([
                'user_id' => $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier(),
                'partial_password_hash' => $partialPasswordHash,
                'exception_message_key' => $exception->getMessageKey(),
                'exception_message_data' => $exception->getMessageData(),
            ]));
    }
}
