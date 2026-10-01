<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument;

use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

final readonly class OtherPublicationUploadMainDocumentRequestDto implements UploadMainDocumentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public Uuid $organisationId,
        public ExternalId $dossierExternalId,
    ) {
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
