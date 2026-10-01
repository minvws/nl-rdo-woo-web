<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Domain\OpenApi\PathParameter\AttachmentExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AnnualReportUploadAttachmentRequest',
    description: 'An annual plan describes in concrete terms what goals a government organisation aims to achieve in '
        . 'that year, how it plans to do so, and what resources (funding) it allocates for this. An annual report '
        . 'describes, in retrospect, what a government organisation actually did, achieved and spent in a given year.',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external'
                . '/{dossierExternalId}/uploads/attachment/external/{attachmentExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
                'attachmentExternalId' => new AttachmentExternalIdLink(),
            ],
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            status: Response::HTTP_NO_CONTENT,
            controller: AnnualReportUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: self::ROUTE_NAME_UPLOAD,
            processor: AnnualReportUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['AnnualReport'],
        summary: 'Upload an attachment file',
        description: 'Uploads the file for an attachment that was already declared via the dossier\'s `attachments` field.',
    ),
)]
final readonly class AnnualReportUploadAttachmentResource
{
    public const string ROUTE_NAME_UPLOAD = 'annual_report_attachment_upload';
}
