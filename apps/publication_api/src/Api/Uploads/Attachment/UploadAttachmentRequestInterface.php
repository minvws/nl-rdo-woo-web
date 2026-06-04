<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\Attachment;

use Psr\Http\Message\StreamInterface;
use Shared\ValueObject\ExternalId;

interface UploadAttachmentRequestInterface
{
    public function getAttachmentExternalId(): ExternalId;

    public function getContent(): StreamInterface;

    public function getDossierExternalId(): ExternalId;

    public function getOrganisationId(): string;
}
