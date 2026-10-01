<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\PathParameter;

use ApiPlatform\Metadata\Link;
use PublicationApi\Domain\OpenApi\Metadata\UuidOpenApiSchema;

final class OrganisationIdLink extends Link
{
    public function __construct()
    {
        parent::__construct(
            description: 'The unique identifier of the organisation.',
            schema: UuidOpenApiSchema::SCHEMA,
        );
    }
}
