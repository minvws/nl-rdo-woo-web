<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema\Component;

use ApiPlatform\OpenApi\Model\Response;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('publication_api.open_api.responses_component_provider')]
interface OpenApiResponsesComponentProvider
{
    /**
     * @return array<string,Response>
     */
    public function getResponses(): array;
}
