<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Security\LoginActivity\LoginActivity;
use App\Service\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

readonly class TwoFactorLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $doctrine,
    ) {
    }

    #[AsEventListener(event: TwoFactorAuthenticationEvents::FAILURE)]
    public function onFailure(TwoFactorAuthenticationEvent $event): void
    {
        /** @var User $user */
        $user = $event->getToken()->getUser();

        $this->logger->log('info', 'Two factor attempt failed', [
            'user_id' => $user->getUserIdentifier(),
        ]);
    }

    #[AsEventListener(event: TwoFactorAuthenticationEvents::SUCCESS)]
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

        $loginActivity = new LoginActivity();
        $loginActivity->setAccount($user);
        $loginActivity->setLoginAt(new \DateTimeImmutable());
        $this->doctrine->persist($loginActivity);
        $this->doctrine->flush();
    }
}
