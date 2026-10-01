<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'OtherPublicationUploadMainDocumentRequest',
    description: 'This document has been made public as part of the effort obligation under the Dutch Open Government Act (Wet open overheid).',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: OtherPublicationUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: OtherPublicationUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['OtherPublication'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class OtherPublicationUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'other_publication_main_document_upload';
}
