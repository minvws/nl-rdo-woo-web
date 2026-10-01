<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Uploads\Document\UploadDocumentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

final readonly class WooDecisionUploadDocumentRequestDto implements UploadDocumentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public Uuid $organisationId,
        public ExternalId $dossierExternalId,
        public ExternalId $documentExternalId,
    ) {
    }

    public function getContent(): StreamInterface
    {
        return $this->content;
    }

    public function getDocumentExternalId(): ExternalId
    {
        return $this->documentExternalId;
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
