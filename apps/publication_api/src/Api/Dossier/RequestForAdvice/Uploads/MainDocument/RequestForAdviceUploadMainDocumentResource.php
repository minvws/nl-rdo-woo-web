<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'RequestForAdviceUploadMainDocumentRequest',
    description: 'The request for advice on a draft law or regulation, or on another subject.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: RequestForAdviceUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: RequestForAdviceUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['RequestForAdvice'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class RequestForAdviceUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'request_for_advice_main_document_upload';
}
