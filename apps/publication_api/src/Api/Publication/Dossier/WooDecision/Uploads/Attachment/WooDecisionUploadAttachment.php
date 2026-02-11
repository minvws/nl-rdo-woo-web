<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/E:{dossierExternalId}/uploads/attachment/E:{attachmentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-fA-F-]+',
                'dossierId' => '[0-9a-fA-F-]+',
                'externalId' => '[0-9a-fA-F-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: WooDecisionUploadAttachmentController::class,
            input: false,
            output: false,
            deserialize: false,
            provider: WooDecisionUploadAttachmentProvider::class,
            processor: WooDecisionUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Upload'],
    ),
)]
final readonly class WooDecisionUploadAttachment
{
    public function __construct(
        public string $content,
        public string $organisationId,
        public string $dossierExternalId,
        public string $attachmentExternalId,
    ) {
    }
}
