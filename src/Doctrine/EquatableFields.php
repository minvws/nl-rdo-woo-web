<?php

declare(strict_types=1);

namespace Shared\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use Shared\ValueObject\Equatable;

use function is_a;

final class EquatableFields
{
    /**
     * @param ClassMetadata<object> $metadata
     *
     * @return list<string>
     */
    public static function of(ClassMetadata $metadata): array
    {
        $fields = [];
        foreach ($metadata->fieldMappings as $field => $mapping) {
            // Fields inlined from an embeddable are keyed by a dotted path (e.g. "address.street"),
            // not a property on this class:
            if ($mapping->declaredField !== null) {
                continue;
            }

            $type = new ReflectionProperty($mapping->declared ?? $metadata->name, $field)->getType();
            if (self::isEquatableType($type)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private static function isEquatableType(?ReflectionType $type): bool
    {
        return $type instanceof ReflectionNamedType
            && ! $type->isBuiltin()
            && is_a($type->getName(), Equatable::class, true);
    }
}
