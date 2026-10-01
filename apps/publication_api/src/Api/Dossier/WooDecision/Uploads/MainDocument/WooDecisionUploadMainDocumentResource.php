<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'WooDecisionUploadMainDocumentRequest',
    description: 'A decision on a request for information under the Dutch Open Government Act (Wet open overheid, '
        . 'Woo), including the published documents and any exemption grounds applied.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: WooDecisionUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: WooDecisionUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['WooDecision'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class WooDecisionUploadMainDocumentResource
{
    public const string ROUTE_NAME_UPLOAD = 'woo_decision_main_document_upload';
}
