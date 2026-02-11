<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Mockery;
use Shared\Service\Security\User;
use Shared\Service\Security\UserChecker;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends UnitTestCase
{
    public function testCheckPreAuth(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('isDisabled')
            ->andReturn(false);

        $userChecker = new UserChecker();
        $userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthIfNoUser(): void
    {
        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'user';
            }
        };

        $userChecker = new UserChecker();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $userChecker->checkPreAuth($user);
    }

    public function testCheckPreAuthIfDisabled(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('isDisabled')
            ->andReturn(true);

        $userChecker = new UserChecker();

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $userChecker->checkPreAuth($user);
    }

    public function testCheckPostAuth(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('isDisabled')
            ->andReturn(false);

        $userChecker = new UserChecker();
        $userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthIfNoUser(): void
    {
        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'user';
            }
        };

        $userChecker = new UserChecker();

        $this->expectException(AccessDeniedException::class);
        $userChecker->checkPostAuth($user);
    }

    public function testCheckPostAuthIfDisabled(): void
    {
        $user = Mockery::mock(User::class);
        $user->expects('isDisabled')
            ->andReturn(true);

        $userChecker = new UserChecker();

        $this->expectException(AccessDeniedException::class);
        $userChecker->checkPostAuth($user);
    }
}
