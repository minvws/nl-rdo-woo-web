<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

use function in_array;

/**
 * Registers EquatableFieldListener as a preFlush entity listener on every entity that has at least one Equatable
 * field, so entities don't need an #[EntityListeners] attribute to get equality-based change detection.
 *
 * @see EquatableFieldListener the entity listener that is attached
 * @see EquatableFields determines which fields are Equatable
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
final readonly class AttachEquatableFieldListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $metadata = $args->getClassMetadata();

        $listener = ['class' => EquatableFieldListener::class, 'method' => 'preFlush'];
        if (in_array($listener, $metadata->entityListeners[Events::preFlush] ?? [], true)) {
            return;
        }

        if (EquatableFields::of($metadata) === []) {
            return;
        }

        $metadata->addEntityListener(Events::preFlush, EquatableFieldListener::class, 'preFlush');
    }
}
