<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Security\Event\UserDisableEvent;
use App\Service\Security\Event\UserEnableEvent;
use App\Service\Security\Event\UserUpdatedEvent;
use App\Service\UserService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Uuid;

class UserServiceTest extends UnitTestCase
{
    private UserRepository&MockInterface $userRepository;
    private UserPasswordHasherInterface&MockInterface $passwordHasher;
    private TotpAuthenticatorInterface&MockInterface $totp;
    private LoggerInterface&MockInterface $logger;
    private EventDispatcherInterface&MockInterface $eventDispatcher;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private UserService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = \Mockery::mock(UserRepository::class);
        $this->passwordHasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $this->totp = \Mockery::mock(TotpAuthenticatorInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);

        $this->service = new UserService(
            $this->userRepository,
            $this->passwordHasher,
            $this->totp,
            $this->logger,
            $this->tokenStorage,
            $this->eventDispatcher,
        );
    }

    public function testGet2faQrCodeImage(): void
    {
        $user = \Mockery::mock(User::class);
        $this->totp->expects('getQRContent')->with($user)->andReturn('fooBar');

        $this->assertMatchesTextSnapshot($this->service->get2faQrCodeImage($user));
    }

    public function testUpdateRoles(): void
    {
        $roles = ['FOO', 'BAR'];

        $oldUser = \Mockery::mock(User::class);

        $updatedUser = \Mockery::mock(User::class);
        $updatedUser->shouldReceive('getId')->andReturn(Uuid::v6());
        $updatedUser->expects('getRoles')->andReturn([]);
        $updatedUser->expects('setRoles')->with($roles);

        $actor = \Mockery::mock(User::class);
        $actor->shouldReceive('getRoles')->andReturn($roles);

        $this->userRepository->expects('save')->with($updatedUser, true);

        $this->logger->expects('log')->with('info', 'User roles updated', \Mockery::any());

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (UserUpdatedEvent $event) use ($oldUser, $updatedUser, $actor): bool {
                self::assertEquals($oldUser, $event->oldUser);
                self::assertEquals($updatedUser, $event->updatedUser);
                self::assertEquals($actor, $event->actor);

                return true;
            }
        ));

        $this->service->updateRoles($actor, $oldUser, $updatedUser, $roles);
    }

    public function testDisable(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');

        $actor = \Mockery::mock(User::class);

        $user->expects('setEnabled')->with(false);
        $this->userRepository->expects('save')->with($user, true);

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (UserDisableEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->user);
                self::assertEquals($actor, $event->actor);

                return true;
            }
        ));

        $this->service->disable($user, $actor);
    }

    public function testEnable(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('getAuditId')->andReturn('foo123');

        $actor = \Mockery::mock(User::class);

        $user->expects('setEnabled')->with(true);
        $this->userRepository->expects('save')->with($user, true);

        $this->eventDispatcher->expects('dispatch')->with(\Mockery::on(
            static function (UserEnableEvent $event) use ($user, $actor): bool {
                self::assertEquals($user, $event->user);
                self::assertEquals($actor, $event->actor);

                return true;
            }
        ));

        $this->service->enable($user, $actor);
    }
}
