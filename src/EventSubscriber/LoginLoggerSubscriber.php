<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Security\Authorization\AuthorizationMatrix;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * This listener will log successful and failed login attempts.
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 */
class LoginLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AuditLogger $auditLogger,
        private readonly RouterInterface $router,
        private readonly AuthorizationMatrix $authorizationMatrix,
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

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onAuthenticationSuccess',
            LoginFailureEvent::class => 'onAuthenticationFailure',
            LogoutEvent::class => 'onLogout',
        ];
    }
}
