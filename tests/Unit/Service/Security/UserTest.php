<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\LoginActivity\LoginActivity;
use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;

class UserTest extends UnitTestCase
{
    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail($email = 'foo@bar.com');

        self::assertEquals($email, $user->getEmail());
        self::assertEquals($email, $user->getUserIdentifier());
        self::assertEquals($email, $user->getTotpAuthenticationUsername());
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $user->setRoles([
            Roles::ROLE_DOSSIER_ADMIN,
            Roles::ROLE_SUPER_ADMIN,
        ]);

        self::assertTrue($user->hasRole(Roles::ROLE_DOSSIER_ADMIN));
        self::assertFalse($user->hasRole(Roles::ROLE_ORGANISATION_ADMIN));

        self::assertEquals(
            [
                Roles::ROLE_DOSSIER_ADMIN,
                Roles::ROLE_SUPER_ADMIN,
            ],
            $user->getRoles(),
        );
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $user->setPassword($password = 'fooBar');

        self::assertEquals($password, $user->getPassword());
    }

    public function testSetAndGetName(): void
    {
        $user = new User();
        $user->setName($name = 'Foo Bar');

        self::assertEquals($name, $user->getName());
    }

    public function testSetAndGetMfaToken(): void
    {
        $user = new User();
        $user->setMfaToken($token = 'foo-bar');

        self::assertEquals($token, $user->getMfaToken());
    }

    public function testSetAndGetMfaRecovery(): void
    {
        $user = new User();
        $user->setMfaRecovery($tokens = ['foo', 'bar']);

        self::assertEquals($tokens, $user->getMfaRecovery());
        self::assertTrue($user->isBackupCode('foo'));
        self::assertFalse($user->isBackupCode('blurp'));
    }

    public function testInvalidateBackupCode(): void
    {
        $user = new User();
        $user->setMfaRecovery($tokens = ['foo', 'bar']);

        self::assertEquals($tokens, $user->getMfaRecovery());
        self::assertTrue($user->isBackupCode('foo'));

        $user->invalidateBackupCode('foo');

        self::assertFalse($user->isBackupCode('foo'));
        self::assertNotNull($user->getMfaRecovery());
        self::assertEquals(['bar'], array_values($user->getMfaRecovery()));
    }

    public function testSetAndGetEnabled(): void
    {
        $user = new User();
        $user->setEnabled(false);

        self::assertFalse($user->isEnabled());
    }

    public function testSetAndGetChangePwd(): void
    {
        $user = new User();
        $user->setChangepwd(true);

        self::assertTrue($user->isPasswordChangeRequired());
        self::assertTrue($user->isChangepwd());
    }

    public function testSetAndGetOrganisation(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $user = new User();
        $user->setOrganisation($organisation);

        self::assertEquals($organisation, $user->getOrganisation());
    }

    public function testAddAndRemoveLoginActivity(): void
    {
        $user = new User();

        $activity = \Mockery::mock(LoginActivity::class);
        $activity->expects('setAccount')->with($user);

        $user->addLoginActivity($activity);

        self::assertEquals([$activity], $user->getLoginActivities()->toArray());

        $user->removeLoginActivity($activity);

        self::assertEquals([], $user->getLoginActivities()->toArray());
    }
}
