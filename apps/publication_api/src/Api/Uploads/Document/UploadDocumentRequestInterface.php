<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\Document;

use Psr\Http\Message\StreamInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

interface UploadDocumentRequestInterface
{
    public function getContent(): StreamInterface;

    public function getDocumentExternalId(): ExternalId;

    public function getDossierExternalId(): ExternalId;

    public function getOrganisationId(): Uuid;
}
