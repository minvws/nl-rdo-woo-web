<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

final class UuidOpenApiSchema
{
    public const array SCHEMA = [
        'type' => 'string',
        'format' => 'uuid',
    ];
}
