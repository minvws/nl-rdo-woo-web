<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'CovenantUploadAttachmentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant'
            . '/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: CovenantUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'covenant_attachment_upload',
            processor: CovenantUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Covenant'],
    ),
)]
final readonly class CovenantUploadAttachmentResource
{
}
