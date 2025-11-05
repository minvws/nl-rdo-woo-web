<?php

declare(strict_types=1);

namespace App\Tests\Integration\Public\Robots;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RobotsControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
    }
}
