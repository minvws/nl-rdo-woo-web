<?php

declare(strict_types=1);

namespace PublicationApi\Api;

use InvalidArgumentException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\ValueObject\ExternalId;

class ExternalIdFactory
{
    public static function create(string $externalId): ExternalId
    {
        try {
            return ExternalId::create($externalId);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::fromThrowable($invalidArgumentException);
        }
    }
}
