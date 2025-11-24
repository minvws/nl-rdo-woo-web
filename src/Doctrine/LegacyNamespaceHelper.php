<?php

declare(strict_types=1);

namespace Shared\Doctrine;

final class LegacyNamespaceHelper
{
    /**
     * Normalizes a class name from the old App\ namespace to the new Shared\ namespace.
     *
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return class-string<T>
     */
    public static function normalizeClassName(string $className): string
    {
        if (str_starts_with($className, 'App\\')) {
            $normalizedClassName = 'Shared\\' . substr($className, 4);

            if (class_exists($normalizedClassName)) {
                /** @var class-string<T> */
                return $normalizedClassName;
            }
        }

        return $className;
    }
}
