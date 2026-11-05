<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Mockery;
use Mockery\MockInterface;
use Shared\Doctrine\EquatableFieldListener;
use Shared\Tests\Unit\Doctrine\Fixtures\EquatableFieldsEntity;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;

final class EquatableFieldListenerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private UnitOfWork&MockInterface $unitOfWork;
    private EquatableFieldListener $listener;

    protected function setUp(): void
    {
        $this->unitOfWork = Mockery::mock(UnitOfWork::class);
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->listener = new EquatableFieldListener();
    }

    public function testRestoresOriginalInstancesWhenValuesAreEqual(): void
    {
        $originalTitle = DossierTitle::create('Original title');
        $originalDate = PlainDate::create('2025-01-01');
        $originalInheritedTitle = DossierTitle::create('Inherited title');

        $entity = new EquatableFieldsEntity(DossierTitle::create('Original title'), PlainDate::create('2025-01-01'));
        $entity->setInheritedTitle(DossierTitle::create('Inherited title'));

        $this->preFlush($entity, [
            'title' => $originalTitle,
            'date' => $originalDate,
            'inheritedTitle' => $originalInheritedTitle,
        ]);

        self::assertSame($originalTitle, $entity->title);
        self::assertSame($originalDate, $entity->date);
        self::assertSame($originalInheritedTitle, $entity->getInheritedTitle());
    }

    public function testKeepsNewInstanceWhenValueIsDifferent(): void
    {
        $newTitle = DossierTitle::create('New title');
        $entity = new EquatableFieldsEntity($newTitle);

        $this->preFlush($entity, ['title' => DossierTitle::create('Original title')]);

        self::assertSame($newTitle, $entity->title);
    }

    public function testKeepsNullWhenOriginalValueWasSet(): void
    {
        $entity = new EquatableFieldsEntity(DossierTitle::create('Title'), date: null);

        $this->preFlush($entity, ['date' => PlainDate::create('2025-01-01')]);

        self::assertNull($entity->date);
    }

    public function testKeepsNewValueWhenOriginalValueWasNull(): void
    {
        $newDate = PlainDate::create('2025-01-01');
        $entity = new EquatableFieldsEntity(DossierTitle::create('Title'), $newDate);

        $this->preFlush($entity, ['date' => null]);

        self::assertSame($newDate, $entity->date);
    }

    public function testIgnoresFieldsThatAreNotEquatable(): void
    {
        $newCreatedAt = new DateTimeImmutable('2025-01-01');
        $entity = new EquatableFieldsEntity(DossierTitle::create('Title'), createdAt: $newCreatedAt);

        $this->preFlush($entity, ['createdAt' => new DateTimeImmutable('2025-01-01')]);

        self::assertSame($newCreatedAt, $entity->createdAt);
    }

    public function testSkipsEntityWithoutOriginalData(): void
    {
        $title = DossierTitle::create('Title');
        $entity = new EquatableFieldsEntity($title);

        $this->entityManager->expects('getUnitOfWork')->andReturn($this->unitOfWork);
        $this->unitOfWork->expects('getOriginalEntityData')->with($entity)->andReturn([]);

        $this->listener->preFlush($entity, new PreFlushEventArgs($this->entityManager));

        $this->entityManager->shouldNotHaveReceived('getClassMetadata');
        self::assertSame($title, $entity->title);
    }

    /**
     * @param array<string, mixed> $originalData
     */
    private function preFlush(EquatableFieldsEntity $entity, array $originalData): void
    {
        $this->entityManager->expects('getUnitOfWork')->andReturn($this->unitOfWork);
        $this->entityManager->expects('getClassMetadata')
            ->with(EquatableFieldsEntity::class)
            ->andReturn(EquatableFieldsEntity::metadata());
        $this->unitOfWork->expects('getOriginalEntityData')->with($entity)->andReturn($originalData);

        $this->listener->preFlush($entity, new PreFlushEventArgs($this->entityManager));
    }
}
