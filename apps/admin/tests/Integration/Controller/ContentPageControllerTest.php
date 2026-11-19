<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Controller;

use Admin\Tests\Integration\AdminWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Service\Security\Roles;
use Shared\Tests\Factory\UserFactory;

final class ContentPageControllerTest extends AdminWebTestCase
{
    #[DataProvider('contentPagesResponseCodeData')]
    public function testContentPagesResponseCode(string $role, int $expectedResponseCode): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->create(['roles' => [$role]]);

        $client
            ->loginUser($user, 'balie')
            ->request('GET', '/balie/content-pages');

        $this->assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<string,array{role:string, expectedResponseCode:int}>
     */
    public static function contentPagesResponseCodeData(): array
    {
        return [
            Roles::ROLE_SUPER_ADMIN => ['role' => Roles::ROLE_SUPER_ADMIN, 'expectedResponseCode' => 200],
            Roles::ROLE_CONTENT_ADMIN => ['role' => Roles::ROLE_CONTENT_ADMIN, 'expectedResponseCode' => 200],
            Roles::ROLE_ORGANISATION_ADMIN => ['role' => Roles::ROLE_ORGANISATION_ADMIN, 'expectedResponseCode' => 403],
            Roles::ROLE_DOSSIER_ADMIN => ['role' => Roles::ROLE_DOSSIER_ADMIN, 'expectedResponseCode' => 403],
            Roles::ROLE_VIEW_ACCESS => ['role' => Roles::ROLE_VIEW_ACCESS, 'expectedResponseCode' => 403],
        ];
    }

    #[DataProvider('superAdminOnlyPathData')]
    public function testContentAdminCannotAccessSuperAdminFunctionality(string $path): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->isEnabled()
            ->asContentAdmin()
            ->create();

        $client
            ->loginUser($user, 'balie')
            ->request('GET', $path);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @return array<string,array{path:string}>
     */
    public static function superAdminOnlyPathData(): array
    {
        return [
            'admin' => ['path' => '/balie/admin'],
            'elastic' => ['path' => '/balie/elastic'],
            'data-checks' => ['path' => '/balie/data-checks'],
        ];
    }
}
