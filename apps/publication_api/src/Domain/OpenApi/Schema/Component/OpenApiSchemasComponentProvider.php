<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema\Component;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('publication_api.open_api.schemas_component_provider')]
interface OpenApiSchemasComponentProvider
{
    /**
     * @return array<string,array<string,mixed>|bool>
     */
    public function getSchemas(): array;
}
