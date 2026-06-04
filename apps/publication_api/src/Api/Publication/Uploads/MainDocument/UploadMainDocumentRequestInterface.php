<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Uploads\MainDocument;

use Psr\Http\Message\StreamInterface;
use Shared\ValueObject\ExternalId;

interface UploadMainDocumentRequestInterface
{
    public function getContent(): StreamInterface;

    public function getDossierExternalId(): ExternalId;

    public function getOrganisationId(): string;
}
