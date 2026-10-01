<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DraftDecisionUploadAttachmentRequest',
    description: 'The drafts of laws and other regulations on which a government organisation has requested advice '
        . 'from an external party. The request for advice itself also falls under this category.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}'
                . '/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
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
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class DraftDecisionUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'draft_decision_attachment_upload';
}
