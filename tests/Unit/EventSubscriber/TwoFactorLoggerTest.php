<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Shared\EventSubscriber\TwoFactorLogger;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TwoFactorLoggerTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private TwoFactorLogger $subscriber;
    private EntityManagerInterface&MockInterface $entityManager;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->subscriber = new TwoFactorLogger(
            $this->logger,
            $this->entityManager,
        );
    }

    public function testOnSuccessLogsMessage(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('getUserIdentifier')->andReturn($userId = 'foo123');

        $request = Mockery::mock(Request::class);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new TwoFactorAuthenticationEvent(
            $request,
            $token,
        );

        $this->logger->expects('log')->with('info', 'Login success', ['user_id' => $userId]);

        $this->entityManager->expects('persist');
        $this->entityManager->expects('flush');

        $this->subscriber->onSuccess($event);
    }

    public function testOnFailureLogsMessage(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('getUserIdentifier')->andReturn($userId = 'foo123');

        $request = Mockery::mock(Request::class);
        $token = Mockery::mock(TokenInterface::class);
        $token->expects('getUser')->andReturn($user);

        $event = new TwoFactorAuthenticationEvent(
            $request,
            $token,
        );

        $this->logger->expects('log')->with('info', 'Two factor attempt failed', ['user_id' => $userId]);

        $this->subscriber->onFailure($event);
    }
}
