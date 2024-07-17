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

    /**
     * @return list<string>
     */
    public function getUuids(): array
    {
        return [
            'df103243-a515-3291-b7bf-7eaa19502f95',
            '0a403de2-54dd-3574-baee-08991e1f6c,a4',
            '79b41831-badf-3786-a07e-3596c73998ed',
            '8396edf9-c786-3c5c-b1df-411d74d85cb1',
            'a9530791-996a-3a87-9380-8a2dca634ce8',
        ];
    }

    private function getEntity(string $uuid): EntityWithFileInfo&MockInterface
    {
        /** @var EntityWithFileInfo&MockInterface $entity */
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn($this->getUuid($uuid));

        return $entity;
    }

    private function getUuid(string $uuid): Uuid&MockInterface
    {
        /** @var Uuid&MockInterface $uuidObject */
        $uuidObject = \Mockery::mock(Uuid::class);
        $uuidObject->shouldReceive('__toString')->andReturn($uuid);

        return $uuidObject;
    }
}
