<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload;

use Mockery;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Uid\Uuid;

final class UploadContextReaderTest extends UnitTestCase
{
    public function testGetDossierId(): void
    {
        $dossierId = $this->getFaker()->uuid();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag(['dossierId' => $dossierId]));

        $uploadContextReader = new UploadContextReader();

        self::assertEquals(Uuid::fromString($dossierId), $uploadContextReader->getDossierId($uploadEntity));
    }

    public function testGetDossierIdWhenEmpty(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag());
        $uploadEntity->expects('getId')->andReturn(Uuid::fromString($this->getFaker()->uuid()));

        $uploadContextReader = new UploadContextReader();

        $this->expectException(UploadException::class);
        $uploadContextReader->getDossierId($uploadEntity);
    }

    public function testGetDossierIdWhenInvalid(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag(['dossierId' => $this->getFaker()->word()]));
        $uploadEntity->expects('getId')->andReturn(Uuid::fromString($this->getFaker()->uuid()));

        $uploadContextReader = new UploadContextReader();

        $this->expectException(UploadException::class);
        $uploadContextReader->getDossierId($uploadEntity);
    }

    public function testGetDepartmentId(): void
    {
        $departmentId = $this->getFaker()->uuid();

        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag(['departmentId' => $departmentId]));

        $uploadContextReader = new UploadContextReader();

        self::assertEquals(Uuid::fromString($departmentId), $uploadContextReader->getDepartmentId($uploadEntity));
    }

    public function testGetDepartmentIdWhenEmpty(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag());
        $uploadEntity->expects('getId')->andReturn(Uuid::fromString($this->getFaker()->uuid()));

        $uploadContextReader = new UploadContextReader();

        $this->expectException(UploadException::class);
        $uploadContextReader->getDepartmentId($uploadEntity);
    }

    public function testGetDepartmentIdThrowsWhenInvalid(): void
    {
        $uploadEntity = Mockery::mock(UploadEntity::class);
        $uploadEntity->expects('getContext')->andReturn(new InputBag(['departmentId' => $this->getFaker()->word()]));
        $uploadEntity->expects('getId')->andReturn(Uuid::fromString($this->getFaker()->uuid()));

        $uploadContextReader = new UploadContextReader();

        $this->expectException(UploadException::class);
        $uploadContextReader->getDepartmentId($uploadEntity);
    }
}
