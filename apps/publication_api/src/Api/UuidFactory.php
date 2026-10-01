<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use InvalidArgumentException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Symfony\Component\Uid\Uuid;

final class UuidFactory
{
    /**
     * @throws ValidationException
     */
    public static function create(string $uuid): Uuid
    {
        try {
            return Uuid::fromString($uuid);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::fromThrowable($invalidArgumentException);
        }
    }
}
