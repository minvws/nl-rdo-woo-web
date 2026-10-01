<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'InvestigationReportUploadAttachmentRequest',
    description: 'Government organisations regularly commission research, carried out by external researchers or by '
        . 'their own staff. An investigation report is characterised by a research question, which is answered based '
        . 'on an analysis of collected information or data.',
    operations: [
        new Put(
            // @phpcs:ignore Generic.Files.LineLength.TooLong
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: InvestigationReportUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: InvestigationReportUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class InvestigationReportUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'investigation_report_attachment_upload';
}
