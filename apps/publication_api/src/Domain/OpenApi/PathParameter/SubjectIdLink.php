<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\PathParameter;

use ApiPlatform\Metadata\Link;
use PublicationApi\Domain\OpenApi\Metadata\UuidOpenApiSchema;

final class SubjectIdLink extends Link
{
    public function __construct()
    {
        parent::__construct(
            identifiers: ['id'],
            description: 'The unique identifier of the subject.',
            schema: UuidOpenApiSchema::SCHEMA,
        );
    }
}
