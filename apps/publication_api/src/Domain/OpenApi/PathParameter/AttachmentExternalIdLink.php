<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\PathParameter;

use ApiPlatform\Metadata\Link;
use PublicationApi\Domain\OpenApi\Metadata\ExternalIdOpenApiSchema;

final class AttachmentExternalIdLink extends Link
{
    public function __construct()
    {
        parent::__construct(
            description: 'The external identifier of the attachment, unique within the dossier.',
            schema: ExternalIdOpenApiSchema::SCHEMA,
        );
    }
}
