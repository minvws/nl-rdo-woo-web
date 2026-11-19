<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestDtoFactory;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DispositionUploadMainDocument',
    description: 'A disposition is a formal, written decision by a government organisation. Dispositions concern '
        . 'specific, individual cases (for example a company, a resident, a violation) — they do not apply to '
        . 'everyone.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: UploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: DispositionUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Disposition'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.'
            . "\n\nWhen an Excel file (xlsx or xls) is uploaded, its first row is used as the column names. Hidden columns are shown."
            . ' Macros and formatting are not included.'
            . ' The file must contain one worksheet and a header row; invalid files return HTTP 422.',
    ),
)]
final readonly class DispositionUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'disposition_main_document_upload';
}
