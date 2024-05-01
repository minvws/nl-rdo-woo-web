<?php

declare(strict_types=1);

namespace App\Tests\Integration\Services\Search;

use App\Service\Search\ConfigFactory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('search')]
final class ConfigFactoryTest extends KernelTestCase
{
    public function testItCanBeInitialized(): void
    {
        self::assertInstanceOf(
            ConfigFactory::class,
            self::getContainer()->get(ConfigFactory::class)
        );
    }
}
