<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mockery;
use Shared\Doctrine\AttachEquatableFieldListener;
use Shared\Doctrine\EquatableFieldListener;
use Shared\Tests\Unit\Doctrine\Fixtures\EquatableFieldsEntity;
use Shared\Tests\Unit\UnitTestCase;

final class AttachEquatableFieldListenerTest extends UnitTestCase
{
    private AttachEquatableFieldListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AttachEquatableFieldListener();
    }

    public function testRegistersPreFlushListenerForEntityWithEquatableFields(): void
    {
        $metadata = EquatableFieldsEntity::metadata();

        $this->listener->loadClassMetadata($this->createEventArgs($metadata));

        self::assertSame(
            [Events::preFlush => [['class' => EquatableFieldListener::class, 'method' => 'preFlush']]],
            $metadata->entityListeners,
        );
    }

    public function testDoesNotRegisterListenerForEntityWithoutEquatableFields(): void
    {
        $metadata = new ClassMetadata(EquatableFieldsEntity::class);
        $metadata->mapField(['fieldName' => 'name', 'type' => Types::STRING]);

        $this->listener->loadClassMetadata($this->createEventArgs($metadata));

        self::assertSame([], $metadata->entityListeners);
    }

    public function testDoesNotRegisterListenerTwice(): void
    {
        $metadata = EquatableFieldsEntity::metadata();

        $this->listener->loadClassMetadata($this->createEventArgs($metadata));
        $this->listener->loadClassMetadata($this->createEventArgs($metadata));

        self::assertCount(1, $metadata->entityListeners[Events::preFlush]);
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function createEventArgs(ClassMetadata $metadata): LoadClassMetadataEventArgs
    {
        return new LoadClassMetadataEventArgs($metadata, Mockery::mock(EntityManagerInterface::class));
    }
}
