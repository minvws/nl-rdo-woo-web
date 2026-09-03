<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Controller;

use Admin\Tests\Integration\AdminWebTestCase;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;

final class DataCheckControllerTest extends AdminWebTestCase
{
    public function testSuperAdminSeesTheAvailableChecks(): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $client->loginUser($user, 'balie')->request('GET', '/balie/data-checks');

        self::assertResponseIsSuccessful();
    }

    public function testNonSuperAdminIsDenied(): void
    {
        $client = static::createClient();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

        $client->loginUser($user, 'balie')
            ->request('GET', '/balie/data-checks');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
