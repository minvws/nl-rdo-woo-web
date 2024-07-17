<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\IndexException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ElasticDocumentTypeTest extends MockeryTestCase
{
    public function testFromEntityForCovenant(): void
    {
        $entity = \Mockery::mock(Covenant::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        self::assertEquals(
            ElasticDocumentType::COVENANT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForCovenantAttachment(): void
    {
        $entity = \Mockery::mock(CovenantAttachment::class);

        self::assertEquals(
            ElasticDocumentType::ATTACHMENT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForUnmappedClass(): void
    {
        $entity = new \stdClass();

        $this->expectException(IndexException::class);
        ElasticDocumentType::fromEntity($entity);
    }

    public function testGetMainTypeValues(): void
    {
        self::assertContains(
            ElasticDocumentType::COVENANT->value,
            ElasticDocumentType::getMainTypeValues(),
        );
    }

    public function testGetSubTypeValues(): void
    {
        self::assertContains(
            ElasticDocumentType::ATTACHMENT->value,
            ElasticDocumentType::getSubTypeValues(),
        );
    }
}
