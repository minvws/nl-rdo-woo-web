<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Minvws\HorseBattery\PasswordGenerator;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Shared\Service\Security\Event\UserDisableEvent;
use Shared\Service\Security\Event\UserEnableEvent;
use Shared\Service\Security\Event\UserResetEvent;
use Shared\Service\Security\Event\UserUpdatedEvent;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Service\UserService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

class UserServiceTest extends UnitTestCase
{
    private UserRepository&MockInterface $userRepository;
    private UserPasswordHasherInterface&MockInterface $passwordHasher;
    private TotpAuthenticatorInterface&MockInterface $totp;
    private LoggerInterface&MockInterface $logger;
    private EventDispatcherInterface&MockInterface $eventDispatcher;
    private TokenStorageInterface&MockInterface $tokenStorage;
    private PasswordGenerator&MockInterface $passwordGenerator;
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = \Mockery::mock(UserRepository::class);
        $this->passwordHasher = \Mockery::mock(UserPasswordHasherInterface::class);
        $this->totp = \Mockery::mock(TotpAuthenticatorInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $this->tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $this->passwordGenerator = \Mockery::mock(PasswordGenerator::class);

        $this->service = new UserService(
            $this->userRepository,
            $this->passwordHasher,
            $this->totp,
            $this->logger,
            $this->tokenStorage,
            $this->eventDispatcher,
            $this->passwordGenerator,
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

    public function testResetPassword(): void
    {
        $password = 'foo';
        $passwordHash = 'bar';
        $userId = Uuid::v6();

        $user = \Mockery::mock(User::class);
        $user->expects('setPassword')
            ->once()
            ->with($passwordHash);
        $user->expects('setChangepwd')
            ->once()
            ->with(true);
        $user->expects('getId')
            ->once()
            ->andReturn($userId);

        $this->passwordGenerator->expects('generate')
            ->once()
            ->with(4)
            ->andReturn($password);

        $this->passwordHasher->expects('hashPassword')
            ->with($user, $password)
            ->once()
            ->andReturn($passwordHash);

        $this->userRepository->expects('save')
            ->once()
            ->with($user, true);

        $this->logger->expects('info')
            ->once()
            ->with('User password reset', [
                'user' => $userId,
            ]);

        $actor = \Mockery::mock(User::class);
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')
            ->once()
            ->andReturn($actor);

        $this->tokenStorage->expects('getToken')
            ->once()
            ->andReturn($token);

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(\Mockery::on(
                static function (UserResetEvent $event) use ($user, $actor): bool {
                    self::assertEquals($user, $event->user);
                    self::assertEquals($actor, $event->actor);
                    self::assertTrue($event->resetPassword);
                    self::assertFalse($event->resetTwoFactorAuth);

                    return true;
                }
            ));

        $this->service->resetPassword($user);
    }

    public function testResetTwoFactorAuth(): void
    {
        $totpSecret = $this->getFaker()->slug(1);
        $totpRecoveryCode = $this->getFaker()->slug(1);
        $userId = Uuid::v6();

        $user = \Mockery::mock(User::class);
        $user->expects('setMfaToken')
            ->once()
            ->with($totpSecret);
        $user->expects('setMfaRecovery')
            ->once()
            ->with([
                $totpRecoveryCode,
                $totpRecoveryCode,
                $totpRecoveryCode,
                $totpRecoveryCode,
                $totpRecoveryCode,
            ]);
        $user->expects('getId')
            ->once()
            ->andReturn($userId);

        $this->totp->expects('generateSecret')
            ->once()
            ->andReturn($totpSecret);

        $this->passwordGenerator->expects('generate')
            ->times(5)
            ->with(4)
            ->andReturn($totpRecoveryCode);

        $this->userRepository->expects('save')
            ->once()
            ->with($user, true);

        $this->logger->expects('info')
            ->once()
            ->with('User two factor auth reset', [
                'user' => $userId,
            ]);

        $actor = \Mockery::mock(User::class);
        $token = \Mockery::mock(TokenInterface::class);
        $token->expects('getUser')
            ->once()
            ->andReturn($actor);

        $this->tokenStorage->expects('getToken')
            ->once()
            ->andReturn($token);

        $this->eventDispatcher->expects('dispatch')
            ->once()
            ->with(\Mockery::on(
                static function (UserResetEvent $event) use ($user, $actor): bool {
                    self::assertEquals($user, $event->user);
                    self::assertEquals($actor, $event->actor);
                    self::assertFalse($event->resetPassword);
                    self::assertTrue($event->resetTwoFactorAuth);

                    return true;
                }
            ));

        $this->service->resetTwoFactorAuth($user);
    }
}
