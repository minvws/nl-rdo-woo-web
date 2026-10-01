<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DocumentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'WooDecisionUploadDocumentRequest',
    description: 'A decision on a request for information under the Dutch Open Government Act (Wet open overheid, '
        . 'Woo), including the published documents and any exemption grounds applied.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external'
                . '/{dossierExternalId}/uploads/document/external/{documentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'documentExternalId' => new DocumentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: WooDecisionUploadDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: WooDecisionUploadDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['WooDecision'],
        summary: 'Upload a document file',
        description: 'Uploads the file for a document that was already declared via the dossier\'s `documents` field.',
    ),
)]
final readonly class WooDecisionUploadDocumentResource
{
    public const string ROUTE_NAME_UPLOAD = 'woo_decision_document_upload';
}
