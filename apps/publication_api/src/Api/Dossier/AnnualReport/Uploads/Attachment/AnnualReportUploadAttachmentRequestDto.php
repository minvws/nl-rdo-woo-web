<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment;

use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

final readonly class AnnualReportUploadAttachmentRequestDto implements UploadAttachmentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public Uuid $organisationId,
        public ExternalId $dossierExternalId,
        public ExternalId $attachmentExternalId,
    ) {
    }

    public function getAttachmentExternalId(): ExternalId
    {
        return $this->attachmentExternalId;
    }

    public function getContent(): StreamInterface
    {
        return $this->content;
    }

    public function getDossierExternalId(): ExternalId
    {
        return $this->dossierExternalId;
    }

    public function getOrganisationId(): Uuid
    {
        return $this->organisationId;
    }
}
