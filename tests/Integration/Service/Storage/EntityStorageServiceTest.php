<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Storage;

use App\Service\Storage\EntityStorageService;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EntityStorageServiceTest extends KernelTestCase
{
    use IntegrationTestTrait;

    public function testItCanBeInitialized(): void
    {
        $service = $this->getContainer()->get(EntityStorageService::class);

        $this->assertInstanceOf(EntityStorageService::class, $service);
    }
}
