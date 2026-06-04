<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Security;

use Shared\Service\Security\Roles;
use Shared\Service\Security\User;
use Shared\Service\Security\UserRepository;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\UserFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class UserRepositoryTest extends SharedWebTestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = self::fromContainer(UserRepository::class);
    }

    public function testFindActiveUsersForOrganisationQuery(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => false,
                ],
            ])
            ->create(['organisation' => $organisationOne]);

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => true,
                ],
            ])
            ->create(['organisation' => $organisationTwo]);

        /** @var list<User> $result */
        $result = $this->userRepository
            ->findActiveUsersForOrganisationQuery($organisationOne)
            ->getResult();

        $this->assertCount(1, $result);
    }

    public function testFindDeactivatedUsersForOrganisationQuery(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => false,
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                    'enabled' => false,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => false,
                ],
                [
                    'roles' => [Roles::ROLE_VIEW_ACCESS],
                    'enabled' => true,
                ],
            ])
            ->create(['organisation' => $organisationOne]);

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => true,
                ],
            ])
            ->create(['organisation' => $organisationTwo]);

        /** @var list<User> $result */
        $result = $this->userRepository
            ->findDeactivatedUsersForOrganisationQuery($organisationOne)
            ->getResult();

        $this->assertCount(2, $result);
    }

    public function testFindActiveAdminsQuery(): void
    {
        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_VIEW_ACCESS],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => false,
                ],
            ])
            ->create();

        /** @var list<User> $result */
        $result = $this->userRepository
            ->findActiveAdminsQuery()
            ->getResult();

        $this->assertCount(1, $result);
    }

    public function testFindDeactivatedAdminsQuery(): void
    {
        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_VIEW_ACCESS],
                    'enabled' => true,
                ],
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                    'enabled' => false,
                ],
            ])
            ->create();

        /** @var list<User> $result */
        $result = $this->userRepository
            ->findDeactivatedAdminsQuery()
            ->getResult();

        $this->assertCount(1, $result);
    }
}
