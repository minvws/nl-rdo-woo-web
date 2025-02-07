<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\Index\IndexException;
use App\Tests\Unit\UnitTestCase;

class ElasticDocumentIdTest extends UnitTestCase
{
    public function testForDossier(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($dossierId = 'foo-123');

        self::assertEquals(
            $dossierId,
            ElasticDocumentId::forDossier($dossier),
        );
    }

    public function testForEntityWithFileInfo(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId->toRfc4122')->andReturn($entityId = 'foo-123');

        self::assertEquals(
            $entityId,
            ElasticDocumentId::forEntityWithFileInfo($entity),
        );
    }

    public function testForObjectWithDossier(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId->toRfc4122')->andReturn($dossierId = 'foo-123');

        self::assertEquals(
            $dossierId,
            ElasticDocumentId::forObject($dossier),
        );
    }

    public function testForObjectWithEntityWithFileInfo(): void
    {
        $entity = \Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId->toRfc4122')->andReturn($entityId = 'foo-123');

        self::assertEquals(
            $entityId,
            ElasticDocumentId::forObject($entity),
        );
    }

    public function testForObjectWithUnsupportedObject(): void
    {
        $entity = new \stdClass();

        $this->expectException(IndexException::class);

        ElasticDocumentId::forObject($entity);
    }
}
