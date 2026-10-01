<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DispositionUploadAttachment',
    description: 'A disposition is a formal, written decision by a government organisation. Dispositions concern '
        . 'specific, individual cases (for example a company, a resident, a violation) — they do not apply to '
        . 'everyone.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition'
                . '/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: DispositionUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: DispositionUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Disposition'],
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class DispositionUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'disposition_attachment_upload';
}
