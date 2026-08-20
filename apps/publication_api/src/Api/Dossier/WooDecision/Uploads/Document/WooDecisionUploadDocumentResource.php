<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'WooDecisionUploadDocumentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external'
                . '/{dossierExternalId}/uploads/document/external/{documentExternalId}',
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
    ),
)]
final readonly class WooDecisionUploadDocumentResource
{
    public const string ROUTE_NAME_UPLOAD = 'woo_decision_document_upload';
}
