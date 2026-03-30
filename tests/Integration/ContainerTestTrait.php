<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use UnexpectedValueException;

use function get_class;
use function sprintf;

trait ContainerTestTrait
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function fromContainer(string $class): object
    {
        $instance = self::getContainer()->get($class);

        if (! $instance instanceof $class) {
            throw new UnexpectedValueException(sprintf('Expected %s, got ', $class) . get_class($instance));
        }

        return $instance;
    }
}
