<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Security;

use App\Service\Security\Roles;
use App\Service\Security\User;
use App\Service\Security\UserRepository;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(UserRepository::class);
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
        $result = $this->repository
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
        $result = $this->repository
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
        $result = $this->repository
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
        $result = $this->repository
            ->findDeactivatedAdminsQuery()
            ->getResult();

        $this->assertCount(1, $result);
    }
}
