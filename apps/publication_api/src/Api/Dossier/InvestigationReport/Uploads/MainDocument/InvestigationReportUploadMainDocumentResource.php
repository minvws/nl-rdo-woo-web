<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'InvestigationReportUploadMainDocumentRequest',
    description: 'Government organisations regularly commission research, carried out by external researchers or by '
        . 'their own staff. An investigation report is characterised by a research question, which is answered based '
        . 'on an analysis of collected information or data.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}/uploads/main-document',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: InvestigationReportUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            processor: InvestigationReportUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
        summary: 'Upload the main document file',
        description: 'Uploads the file for the dossier\'s main document, which must already be declared via `mainDocument`.',
    ),
)]
final readonly class InvestigationReportUploadMainDocumentResource
{
    public const string ROUTE_NAME_MAIN_DOCUMENT_UPLOAD = 'investigation_report_main_document_upload';
}
