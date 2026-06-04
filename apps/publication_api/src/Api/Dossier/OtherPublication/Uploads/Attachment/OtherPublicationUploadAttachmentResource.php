<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'OtherPublicationUploadAttachmentRequest',
    operations: [
        new Put(
            // @phpcs:ignore Generic.Files.LineLength.TooLong
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
                'attachmentExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: OtherPublicationUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'other_publication_attachment_upload',
            processor: OtherPublicationUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['OtherPublication'],
    ),
)]
final readonly class OtherPublicationUploadAttachmentResource
{
}
