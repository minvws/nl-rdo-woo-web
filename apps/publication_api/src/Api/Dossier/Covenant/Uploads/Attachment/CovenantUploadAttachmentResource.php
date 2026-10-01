<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'CovenantUploadAttachmentRequest',
    description: 'A covenant is a written set of agreements between a government organisation and one or more other parties.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant'
            . '/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: CovenantUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: CovenantUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Covenant'],
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class CovenantUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'covenant_attachment_upload';
}
