<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\Index\IndexException;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

class ElasticDocumentIdTest extends UnitTestCase
{
    public function testForDossier(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId->toRfc4122')->andReturn($dossierId = 'foo-123');

        self::assertEquals(
            $dossierId,
            ElasticDocumentId::forDossier($dossier),
        );
    }

    public function testForEntityWithFileInfo(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId->toRfc4122')->andReturn($entityId = 'foo-123');

        self::assertEquals(
            $entityId,
            ElasticDocumentId::forEntityWithFileInfo($entity),
        );
    }

    public function testForSubject(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId->toRfc4122')->andReturn($subjectId = 'foo-123');

        self::assertEquals(
            $subjectId,
            ElasticDocumentId::forSubject($subject),
        );
    }

    public function testForObjectWithDossier(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId->toRfc4122')->andReturn($dossierId = 'foo-123');

        self::assertEquals(
            $dossierId,
            ElasticDocumentId::forObject($dossier),
        );
    }

    public function testForObjectWithEntityWithFileInfo(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->expects('getId->toRfc4122')->andReturn($entityId = 'foo-123');

        self::assertEquals(
            $entityId,
            ElasticDocumentId::forObject($entity),
        );
    }

    public function testForObjectWithSubject(): void
    {
        $subject = Mockery::mock(Subject::class);
        $subject->expects('getId->toRfc4122')->andReturn($subjectId = 'foo-123');

        self::assertEquals(
            $subjectId,
            ElasticDocumentId::forObject($subject),
        );
    }

    public function testForObjectWithUnsupportedObject(): void
    {
        $entity = new stdClass();

        $this->expectException(IndexException::class);

        ElasticDocumentId::forObject($entity);
    }
}
