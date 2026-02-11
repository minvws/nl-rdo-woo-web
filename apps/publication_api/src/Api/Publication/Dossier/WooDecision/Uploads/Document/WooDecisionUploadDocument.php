<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/E:{dossierExternalId}/uploads/document/E:{documentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-fA-F-]+',
                'dossierExternalId' => '[0-9a-fA-F-]+',
                'documentExternalId' => '[0-9a-fA-F-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: WooDecisionUploadDocumentController::class,
            input: false,
            output: false,
            deserialize: false,
            provider: WooDecisionUploadDocumentProvider::class,
            processor: WooDecisionUploadDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Upload'],
    ),
)]
final readonly class WooDecisionUploadDocument
{
    public function __construct(
        public string $content,
        public string $organisationId,
        public ExternalId $dossierExternalId,
        public ExternalId $documentExternalId,
    ) {
    }
}
