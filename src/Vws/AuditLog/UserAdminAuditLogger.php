<?php

declare(strict_types=1);

namespace Shared\Vws\AuditLog;

use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\AuditUser;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\AuditLogger\Events\Logging\UserCreatedLogEvent;
use Shared\Service\Security\Event\UserCreatedEvent;
use Shared\Service\Security\Event\UserDisableEvent;
use Shared\Service\Security\Event\UserEnableEvent;
use Shared\Service\Security\Event\UserResetEvent;
use Shared\Service\Security\Event\UserUpdatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class UserAdminAuditLogger
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {
    }

    #[AsEventListener]
    public function onCreated(UserCreatedEvent $event): void
    {
        $actor = $this->getActor($event);

        $this->auditLogger->log((new UserCreatedLogEvent())
            ->asCreate()
            ->withActor($actor)
            ->withTarget($event->user)
            ->withSource('woo')
            ->withData([
                'user_id' => $event->user->getAuditId(),
                'roles' => $event->roles,
            ]));
    }

    #[AsEventListener]
    public function onReset(UserResetEvent $event): void
    {
        $actor = $this->getActor($event);

        $this->auditLogger->log((new ResetCredentialsLogEvent())
            ->asUpdate()
            ->withActor($actor)
            ->withTarget($event->user)
            ->withSource('woo')
            ->withData([
                'user_id' => $event->user->getAuditId(),
                'password_reset' => $event->resetPassword,
                '2fa_reset' => $event->resetTwoFactorAuth,
            ]));
    }

    #[AsEventListener]
    public function onDisable(UserDisableEvent $event): void
    {
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($event->actor)
            ->withTarget($event->user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ACTIVE)
            ->withData([
                'user_id' => $event->user->getAuditId(),
                'enabled' => false,
            ]));
    }

    #[AsEventListener]
    public function onEnable(UserEnableEvent $event): void
    {
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($event->actor)
            ->withTarget($event->user)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ACTIVE)
            ->withData([
                'user_id' => $event->user->getAuditId(),
                'enabled' => true,
            ]));
    }

    #[AsEventListener]
    public function onUpdate(UserUpdatedEvent $event): void
    {
        $this->auditLogger->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($event->actor)
            ->withTarget($event->updatedUser)
            ->withSource('woo')
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_USERDATA)
            ->withData([
                'user_id' => $event->updatedUser->getAuditId(),
                'old' => [
                    'roles' => $event->oldUser->getRoles(),
                ],
                'new' => [
                    'roles' => $event->updatedUser->getRoles(),
                ],
            ])
            ->withPiiData([
                'old' => [
                    'name' => $event->oldUser->getName(),
                    'email' => $event->oldUser->getEmail(),
                ],
                'new' => [
                    'name' => $event->updatedUser->getName(),
                    'email' => $event->updatedUser->getEmail(),
                ],
            ]));
    }

    private function getActor(UserCreatedEvent|UserResetEvent $event): LoggableUser
    {
        $actor = $event->actor;
        if ($actor === null) {
            $actor = new AuditUser('cli user', 'system', [], 'system@localhost');
        }

        return $actor;
    }
}
