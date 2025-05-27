<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\LoginActivity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
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
    public function __construct(
        protected LoggerInterface $logger,
        protected AuditLogger $auditLogger,
        protected EntityManagerInterface $doctrine,
    ) {
    }

    public function onFailure(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        $this->logger->log('info', 'Two factor attempt failed', [
            'user_id' => $user->getUserIdentifier(),
        ]);

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

        $loginActivity = new LoginActivity();
        $loginActivity->setAccount($user);
        $loginActivity->setLoginAt(new \DateTimeImmutable());
        $this->doctrine->persist($loginActivity);
        $this->doctrine->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TwoFactorAuthenticationEvents::FAILURE => 'onFailure',
            TwoFactorAuthenticationEvents::SUCCESS => 'onSuccess',
        ];
    }
}
