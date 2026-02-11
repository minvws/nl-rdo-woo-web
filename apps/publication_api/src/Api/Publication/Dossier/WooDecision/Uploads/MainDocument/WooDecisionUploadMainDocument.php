<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/{dossierId}/uploads/main-document/{uploadId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-fA-F-]+',
                'dossierId' => '[0-9a-fA-F-]+',
                'uploadId' => '[0-9a-fA-F-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: WooDecisionUploadMainDocumentController::class,
            input: false,
            output: false,
            deserialize: false,
            provider: WooDecisionUploadMainDocumentProvider::class,
            processor: WooDecisionUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Upload'],
    ),
)]
final readonly class WooDecisionUploadMainDocument
{
    public function __construct(
        public string $content,
        public string $organisationId,
        public string $dossierId,
        public string $uploadId,
    ) {
    }
}
