<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'CovenantUploadMainDocumentRequest',
    description: 'A covenant is a written set of agreements between a government organisation and one or more other parties.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: CovenantUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: CovenantUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Covenant'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class CovenantUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'covenant_main_document_upload';
}
