<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'InvestigationReportUploadMainDocumentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/E:{dossierExternalId}/uploads/main-document',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: InvestigationReportUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'investigation_report_main_document_upload',
            processor: InvestigationReportUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
    ),
)]
final readonly class InvestigationReportUploadMainDocumentRequestDto implements UploadMainDocumentRequestInterface
{
    public function __construct(
        public StreamInterface $content,
        public string $organisationId,
        public ExternalId $dossierExternalId,
    ) {
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
