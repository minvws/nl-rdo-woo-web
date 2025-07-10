<?php

declare(strict_types=1);

namespace App\Vws\AuditLog;

use App\Service\Security\User;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserLoginTwoFactorFailedEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: TwoFactorAuthenticationEvents::FAILURE, method: 'onFailure')]
#[AsEventListener(event: TwoFactorAuthenticationEvents::SUCCESS, method: 'onSuccess')]
readonly class TwoFactorAuditLogger
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {
    }

    public function onFailure(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        $this->auditLogger->log(
            (new UserLoginTwoFactorFailedEvent())
                ->asExecute()
                ->withActor($user)
                ->withSource('woo')
        );
    }

    public function onSuccess(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();
        if (! $user) {
            return;
        }

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
}
