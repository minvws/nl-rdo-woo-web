<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AdviceUploadAttachmentRequest',
    description: 'A (un)solicited advice on a draft law or regulation, or on another subject.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice'
                . '/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: AdviceUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: AdviceUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Advice'],
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class AdviceUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'advice_attachment_upload';
}
