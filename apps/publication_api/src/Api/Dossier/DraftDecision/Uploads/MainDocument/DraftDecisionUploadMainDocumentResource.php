<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'DraftDecisionUploadMainDocumentRequest',
    description: 'The drafts of laws and other regulations on which a government organisation has requested advice '
        . 'from an external party. The request for advice itself also falls under this category.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: DraftDecisionUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: DraftDecisionUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    security: "is_granted('DraftDecisionFeature')",
    securityMessage: 'feature is not enabled',
    openapi: new Operation(
        tags: ['DraftDecision'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class DraftDecisionUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'draft_decision_main_document_upload';
}
