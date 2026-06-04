<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\AnnualReport\Uploads\Attachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AnnualReportUploadAttachmentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/E:{dossierExternalId}/uploads/attachment/E:{attachmentExternalId}',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: AnnualReportUploadAttachmentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'annual_report_attachment_upload',
            processor: AnnualReportUploadAttachmentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['AnnualReport'],
    ),
)]
final readonly class AnnualReportUploadAttachmentRequestDto implements UploadAttachmentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public string $organisationId,
        public ExternalId $dossierExternalId,
        public ExternalId $attachmentExternalId,
    ) {
    }

    public function getAttachmentExternalId(): ExternalId
    {
        return $this->attachmentExternalId;
    }

    public function getContent(): StreamInterface
    {
        return $this->content;
    }

    public function getDossierExternalId(): ExternalId
    {
        return $this->dossierExternalId;
    }

    public function getOrganisationId(): string
    {
        return $this->organisationId;
    }
}
