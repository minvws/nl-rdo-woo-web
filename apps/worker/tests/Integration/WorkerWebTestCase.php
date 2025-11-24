<?php

declare(strict_types=1);

namespace Worker\Tests\Integration;

use Shared\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WorkerWebTestCase extends WebTestCase
{
    use IntegrationTestTrait;

    public static function getAppId(): string
    {
        return 'worker';
    }
}
