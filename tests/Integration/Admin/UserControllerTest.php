<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Service\Security\Roles;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    use IntegrationTestTrait;

    #[DataProvider('indexResponsCodeData')]
    public function testIndexResponseCode(string $role, int $expectedResponseCode): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$role]]);

        $client
            ->loginUser($user->_real(), 'balie')
            ->request('GET', '/balie/gebruikers');

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<string,array{role:Roles::ROLE_*, expectedResponseCode:int}>
     */
    public static function indexResponsCodeData(): array
    {
        return [
            Roles::ROLE_SUPER_ADMIN => ['role' => Roles::ROLE_SUPER_ADMIN, 'expectedResponseCode' => 200],
            Roles::ROLE_ORGANISATION_ADMIN => ['role' => Roles::ROLE_ORGANISATION_ADMIN, 'expectedResponseCode' => 200],
            Roles::ROLE_DOSSIER_ADMIN => ['role' => Roles::ROLE_DOSSIER_ADMIN, 'expectedResponseCode' => 403],
            Roles::ROLE_VIEW_ACCESS => ['role' => Roles::ROLE_VIEW_ACCESS, 'expectedResponseCode' => 403],
        ];
    }

    /**
     * @param array<Roles> $userToEditRoles
     */
    #[DataProvider('modifyResponsCodeData')]
    public function testModifyResponseCode(string $loggedInUserRole, array $userToEditRoles, int $expectedResponseCode): void
    {
        $client = static::createClient();

        $loggedInUser = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$loggedInUserRole]]);

        $userToEdit = UserFactory::new()
            ->isEnabled()
            ->create([
                'roles' => $userToEditRoles,
                'organisation' => $loggedInUser->getOrganisation(),
            ]);

        $client
            ->loginUser($loggedInUser->_real(), 'balie')
            ->request('GET', \sprintf('/balie/gebruiker/%s', $userToEdit->getId()));

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<string,array{loggedInUserRole:Roles::ROLE_*, userToEditRoles:array<Roles::ROLE_*>, expectedResponseCode:int}>
     */
    public static function modifyResponsCodeData(): array
    {
        return [
            'super_admin_edits_role_super_admin' => [
                'loggedInUserRole' => Roles::ROLE_SUPER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'super_admin_edits_role_super_admin_and_organisation_admin' => [
                'loggedInUserRole' => Roles::ROLE_SUPER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_ORGANISATION_ADMIN, Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'super_admin_edits_role_organisation_admin' => [
                'loggedInUserRole' => Roles::ROLE_SUPER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'super_admin_edits_role_dossier_admin' => [
                'loggedInUserRole' => Roles::ROLE_SUPER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_DOSSIER_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'super_admin_edits_role_view_access' => [
                'loggedInUserRole' => Roles::ROLE_SUPER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_VIEW_ACCESS],
                'expectedResponseCode' => 200,
            ],
            'organisation_admin_edits_role_super_admin' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN],
                'expectedResponseCode' => 302,
            ],
            'organisation_admin_edits_role_super_admin_and_organisation_role' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN, Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 302,
            ],
            'organisation_admin_edits_role_super_admin_and_organisation_role_and_dossier_admin' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN, Roles::ROLE_ORGANISATION_ADMIN, Roles::ROLE_DOSSIER_ADMIN],
                'expectedResponseCode' => 302,
            ],
            'organisation_admin_edits_role_organisation_admin' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'organisation_admin_edits_role_dossier_admin' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_DOSSIER_ADMIN],
                'expectedResponseCode' => 200,
            ],
            'organisation_admin_edits_role_view_access' => [
                'loggedInUserRole' => Roles::ROLE_ORGANISATION_ADMIN,
                'userToEditRoles' => [Roles::ROLE_VIEW_ACCESS],
                'expectedResponseCode' => 200,
            ],
            'dossier_admin_edits_role_super_admin' => [
                'loggedInUserRole' => Roles::ROLE_DOSSIER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'dossier_admin_edits_role_organisation_admin' => [
                'loggedInUserRole' => Roles::ROLE_DOSSIER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'dossier_admin_edits_role_dossier_admin' => [
                'loggedInUserRole' => Roles::ROLE_DOSSIER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_DOSSIER_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'dossier_admin_edits_role_view_access' => [
                'loggedInUserRole' => Roles::ROLE_DOSSIER_ADMIN,
                'userToEditRoles' => [Roles::ROLE_VIEW_ACCESS],
                'expectedResponseCode' => 403,
            ],
            'view_access_edits_role_super_admin' => [
                'loggedInUserRole' => Roles::ROLE_VIEW_ACCESS,
                'userToEditRoles' => [Roles::ROLE_SUPER_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'view_access_edits_role_organisation_admin' => [
                'loggedInUserRole' => Roles::ROLE_VIEW_ACCESS,
                'userToEditRoles' => [Roles::ROLE_ORGANISATION_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'view_access_edits_role_dossier_admin' => [
                'loggedInUserRole' => Roles::ROLE_VIEW_ACCESS,
                'userToEditRoles' => [Roles::ROLE_DOSSIER_ADMIN],
                'expectedResponseCode' => 403,
            ],
            'view_access_edits_role_view_access' => [
                'loggedInUserRole' => Roles::ROLE_VIEW_ACCESS,
                'userToEditRoles' => [Roles::ROLE_VIEW_ACCESS],
                'expectedResponseCode' => 403,
            ],
        ];
    }
}
