<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Shared\ValueObject\Equatable;

/**
 * Doctrine compares object field values by reference, so assigning a new but equal value object marks the field
 * as changed and triggers an UPDATE (and any update side effects such as audit logging). Just before the changeset
 * is computed, this listener puts the original instance back for every Equatable field whose new value is equalTo()
 * the original.
 *
 * This listener is not declared on entities itself; it is attached to every entity with Equatable fields at runtime.
 *
 * @see AttachEquatableFieldListener attaches this listener to entities with Equatable fields
 * @see EquatableFields determines which fields are Equatable
 */
#[AsEntityListener]
final class EquatableFieldListener
{
    /** @var array<class-string, list<string>> */
    private array $equatableFields = [];

    public function preFlush(object $entity, PreFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();

        $originalData = $entityManager->getUnitOfWork()->getOriginalEntityData($entity);
        // A new entity has no original data to compare against:
        if ($originalData === []) {
            return;
        }

        $metadata = $entityManager->getClassMetadata($entity::class);
        $fields = $this->equatableFields[$metadata->name] ??= EquatableFields::of($metadata);

        foreach ($fields as $field) {
            $originalValue = $originalData[$field] ?? null;
            $currentValue = $metadata->getFieldValue($entity, $field);

            if (
                $originalValue !== $currentValue
                && $originalValue instanceof Equatable
                && $originalValue->equalTo($currentValue)
            ) {
                $metadata->setFieldValue($entity, $field, $originalValue);
            }
        }
    }
}
