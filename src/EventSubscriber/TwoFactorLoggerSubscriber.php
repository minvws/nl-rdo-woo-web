<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginTwoFactorFailedEvent;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This listener will log failed two factor attempts.
 */
class TwoFactorLoggerSubscriber implements EventSubscriberInterface
{
    protected LoggerInterface $logger;
    protected AuditLogger $auditLogger;

    public function __construct(LoggerInterface $logger, AuditLogger $auditLogger)
    {
        $this->logger = $logger;
        $this->auditLogger = $auditLogger;
    }

    public function onFailure(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        $this->logger->log('info', 'Two factor attempt failed', [
            'user_id' => $user->getUserIdentifier(),
        ]);

        $this->auditLogger->log((new UserLoginTwoFactorFailedEvent())
            ->asExecute()
            ->withActor($user)
            ->withSource('woo'));
    }

    public static function getSubscribedEvents()
    {
        return [
            TwoFactorAuthenticationEvents::FAILURE => 'onFailure',
        ];
    }
}
