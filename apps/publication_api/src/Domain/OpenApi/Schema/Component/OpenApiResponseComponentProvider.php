<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema\Component;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('publication_api.open_api.response_component_provider')]
interface OpenApiResponseComponentProvider
{
    /**
     * @return array<array-key,OperationResponseDefinition>
     */
    public function getResponses(): array;
}
