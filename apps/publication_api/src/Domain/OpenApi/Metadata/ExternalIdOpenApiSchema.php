<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use Shared\ValueObject\ExternalId;

/**
 * @phpstan-type ExternalIdSchema array{
 *     type: 'string',
 *     format: 'external-id',
 *     minLength: positive-int,
 *     maxLength: positive-int,
 *     pattern: string,
 * }
 */
final class ExternalIdOpenApiSchema
{
    public const array SCHEMA = [
        'type' => 'string',
        'format' => 'external-id',
        'minLength' => ExternalId::MIN_LENGTH,
        'maxLength' => ExternalId::MAX_LENGTH,
        'pattern' => ExternalId::OPENAPI_PATTERN,
    ];
}
