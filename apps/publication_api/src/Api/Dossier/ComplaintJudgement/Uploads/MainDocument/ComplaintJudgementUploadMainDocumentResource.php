<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'ComplaintJudgementUploadMainDocumentRequest',
    description: 'A complaint judgement is the formal letter a government organisation sends to the complainant in response to a complaint.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: ComplaintJudgementUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: ComplaintJudgementUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgement'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class ComplaintJudgementUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'complaint_judgement_main_document_upload';
}
