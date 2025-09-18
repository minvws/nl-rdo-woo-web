<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Service\Security\Roles;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IndexControllerTest extends WebTestCase
{
    use IntegrationTestTrait;

    #[DataProvider('indexRedirectData')]
    public function testIndexRedirect(string $role, string $expectedLocation): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$role]]);

        $client
            ->loginUser($user->_real(), 'balie')
            ->request('GET', '/balie');

        $this->assertResponseRedirects($expectedLocation);
    }

    /**
     * @return array<string,array{role:Roles::ROLE_*, expectedLocation:string}>
     */
    public static function indexRedirectData(): array
    {
        return [
            Roles::ROLE_SUPER_ADMIN => ['role' => Roles::ROLE_SUPER_ADMIN, 'expectedLocation' => '/balie/dossiers'],
            Roles::ROLE_ORGANISATION_ADMIN => ['role' => Roles::ROLE_ORGANISATION_ADMIN, 'expectedLocation' => '/balie/gebruikers'],
            Roles::ROLE_DOSSIER_ADMIN => ['role' => Roles::ROLE_DOSSIER_ADMIN, 'expectedLocation' => '/balie/dossiers'],
            Roles::ROLE_VIEW_ACCESS => ['role' => Roles::ROLE_VIEW_ACCESS, 'expectedLocation' => '/balie/dossiers'],
        ];
    }

    #[DataProvider('adminResponsCodeData')]
    public function testAdminResponseCode(string $role, int $expectedResponseCode): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$role]]);

        $client
            ->loginUser($user->_real(), 'balie')
            ->request('GET', '/balie/admin');

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<string,array{role:Roles::ROLE_*, expectedResponseCode:int}>
     */
    public static function adminResponsCodeData(): array
    {
        return [
            Roles::ROLE_SUPER_ADMIN => ['role' => Roles::ROLE_SUPER_ADMIN, 'expectedResponseCode' => 200],
            Roles::ROLE_ORGANISATION_ADMIN => ['role' => Roles::ROLE_ORGANISATION_ADMIN, 'expectedResponseCode' => 403],
            Roles::ROLE_DOSSIER_ADMIN => ['role' => Roles::ROLE_DOSSIER_ADMIN, 'expectedResponseCode' => 403],
            Roles::ROLE_VIEW_ACCESS => ['role' => Roles::ROLE_VIEW_ACCESS, 'expectedResponseCode' => 403],
        ];
    }
}
