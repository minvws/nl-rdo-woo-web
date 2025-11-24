<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Public\Robots;

use Shared\Tests\Integration\SharedWebTestCase;

class RobotsControllerTest extends SharedWebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
    }
}
