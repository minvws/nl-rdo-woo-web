<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Roles;
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

    public function testFindAllForOrganisationQueryWithSuperAdmin(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                ],
            ])
            ->create(['organisation' => $organisationOne]);

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                ],
            ])
            ->create(['organisation' => $organisationTwo]);

        /** @var list<User> $result */
        $result = $this->repository
            ->findAllForOrganisationQuery($organisationOne, includeSuperAdmins: true)
            ->getResult();

        $this->assertCount(4, $result);
        foreach ($result as $user) {
            if (! $user->hasRole(Roles::ROLE_SUPER_ADMIN)) {
                $this->assertEquals($organisationOne->getId(), $user->getOrganisation()->getId());
            }
        }
    }

    public function testFindAllForOrganisationQueryWithoutSuperAdmin(): void
    {
        $organisationOne = OrganisationFactory::createOne();
        $organisationTwo = OrganisationFactory::createOne();

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                ],
            ])
            ->create(['organisation' => $organisationOne]);

        UserFactory::new()
            ->sequence([
                [
                    'roles' => [Roles::ROLE_SUPER_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_ORGANISATION_ADMIN],
                ],
                [
                    'roles' => [Roles::ROLE_DOSSIER_ADMIN],
                ],
            ])
            ->create(['organisation' => $organisationTwo]);

        /** @var list<User> $result */
        $result = $this->repository
            ->findAllForOrganisationQuery($organisationTwo, includeSuperAdmins: false)
            ->getResult();

        $this->assertCount(2, $result);
        foreach ($result as $user) {
            $this->assertEquals($organisationTwo->getId(), $user->getOrganisation()->getId());
        }
    }
}
