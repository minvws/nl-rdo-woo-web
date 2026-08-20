<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DraftDecisionUploadMainDocumentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}/uploads/main-document',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: DraftDecisionUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: DraftDecisionUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    security: "is_granted('DraftDecisionFeature')",
    securityMessage: 'feature is not enabled',
    openapi: new Operation(
        tags: ['DraftDecision'],
    ),
)]
final readonly class DraftDecisionUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'draft_decision_main_document_upload';
}
