<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Storage\Streams;

/**
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
abstract class StreamWrapper
{
    /** @var resource */
    public $context;

    /**
     * @var array<class-string<StreamWrapper>,true>
     */
    protected static array $registered = [];

    abstract public static function getName(): string;

    public static function register(): void
    {
        if (isset(static::$registered[static::class])) {
            return;
        }

        stream_wrapper_register(static::getName(), static::class);
        static::$registered[static::class] = true;
    }

    public static function unregister(): void
    {
        if (! isset(static::$registered[static::class])) {
            return;
        }

        stream_wrapper_unregister(static::getName());
        unset(static::$registered[static::class]);
    }

    public static function getPath(string $path): string
    {
        return sprintf('%s://%s', static::getName(), $path);
    }
}
