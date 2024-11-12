<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Storage;

use App\Entity\EntityWithFileInfo;
use App\Service\Storage\StorageRootPathGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class StorageRootPathGeneratorTest extends UnitTestCase
{
    public function testInvoke(): void
    {
        $generator = new StorageRootPathGenerator();

        $mapped = array_map(
            fn (string $uuid): array => [
                'input' => $uuid,
                'output' => $generator($this->getEntity($uuid)),
            ],
            $this->getUuids(),
        );

        $this->assertMatchesYamlSnapshot($mapped);
    }

    public function testFromUuid(): void
    {
        $generator = new StorageRootPathGenerator();

        $mapped = array_map(
            fn (string $uuid): array => [
                'input' => $uuid,
                'output' => $generator->fromUuid(Uuid::fromString($uuid)),
            ],
            $this->getUuids(),
        );

        $this->assertMatchesYamlSnapshot($mapped);
    }

    /**
     * @return list<string>
     */
    public function getUuids(): array
    {
        return [
            '1ef93cd3-6749-6180-ac5e-1bd5a779c2cd',
            '1ef93cd3-6749-6270-8ef3-1bd5a779c2cd',
            '1ef93cd3-6749-628e-a672-1bd5a779c2cd',
            '28239ea6-e320-428a-b88d-a6d35294efef',
            '3b293bf3-ffa4-4254-b3fb-5d6ae95d750d',
        ];
    }

    private function getEntity(string $uuid): EntityWithFileInfo&MockInterface
    {
        /** @var EntityWithFileInfo&MockInterface $entity */
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::fromString($uuid));

        return $entity;
    }
}
