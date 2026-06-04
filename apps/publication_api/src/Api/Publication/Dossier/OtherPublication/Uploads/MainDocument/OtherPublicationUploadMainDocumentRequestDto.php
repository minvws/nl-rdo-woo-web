<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'OtherPublicationUploadMainDocumentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/E:{dossierExternalId}/uploads/main-document',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: OtherPublicationUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'other_publication_main_document_upload',
            processor: OtherPublicationUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['OtherPublication'],
    ),
)]
final readonly class OtherPublicationUploadMainDocumentRequestDto implements UploadMainDocumentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public string $organisationId,
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

    public function getOrganisationId(): string
    {
        return $this->organisationId;
    }
}
