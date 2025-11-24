<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Storage;

use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Integration\SharedWebTestCase;

final class EntityStorageServiceTest extends SharedWebTestCase
{
    public function testItCanBeInitialized(): void
    {
        $service = $this->getContainer()->get(EntityStorageService::class);

        $this->assertInstanceOf(EntityStorageService::class, $service);
    }
}
