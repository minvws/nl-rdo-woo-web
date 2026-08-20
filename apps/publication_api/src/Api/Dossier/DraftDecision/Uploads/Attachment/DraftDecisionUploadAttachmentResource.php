<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DraftDecisionUploadAttachmentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}'
                . '/uploads/attachment/external/{attachmentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
                'attachmentExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: DraftDecisionUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: DraftDecisionUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    security: "is_granted('DraftDecisionFeature')",
    securityMessage: 'feature is not enabled',
    openapi: new Operation(
        tags: ['DraftDecision'],
    ),
)]
final readonly class DraftDecisionUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'draft_decision_attachment_upload';
}
