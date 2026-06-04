<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema\Component;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('publication_api.open_api.common_responses_provider')]
interface OpenApiCommonResponsesProvider
{
    /**
     * @return array<array-key,OperationResponseDefinition>
     */
    public function getCommonResponses(): array;
}
