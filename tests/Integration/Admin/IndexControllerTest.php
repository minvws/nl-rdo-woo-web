<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Roles;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IndexControllerTest extends WebTestCase
{
    use IntegrationTestTrait;

    public function testAdminAsSuperAdmin(): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $client
            ->loginUser($user->_real(), 'balie')
            ->request('GET', '/balie/admin');

        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('rolesData')]
    public function testAdminRedirectsUser(string $role): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$role]]);

        $client
            ->loginUser($user->_real(), 'balie')
            ->request('GET', '/balie/admin');

        $this->assertResponseRedirects('/balie/dossiers', 302);
    }

    /**
     * @return array<string,array{role:Roles::ROLE_*}>
     */
    public static function rolesData(): array
    {
        return [
            Roles::ROLE_ORGANISATION_ADMIN => ['role' => Roles::ROLE_ORGANISATION_ADMIN],
            Roles::ROLE_DOSSIER_ADMIN => ['role' => Roles::ROLE_DOSSIER_ADMIN],
            Roles::ROLE_VIEW_ACCESS => ['role' => Roles::ROLE_VIEW_ACCESS],
        ];
    }
}
