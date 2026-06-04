<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement\Uploads\MainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'ComplaintJudgementUploadMainDocumentRequest',
    operations: [
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/E:{dossierExternalId}/uploads/main-document',
            inputFormats: ['binary' => ['application/octet-stream']],
            outputFormats: [],
            requirements: [
                'organisationId' => '[0-9a-zA-Z-]+',
                'dossierExternalId' => '[0-9a-zA-Z-]+',
            ],
            status: Response::HTTP_NO_CONTENT,
            controller: ComplaintJudgementUploadMainDocumentRequestDtoFactory::class,
            input: false,
            output: false,
            read: false,
            deserialize: false,
            name: 'complaint_judgement_main_document_upload',
            processor: ComplaintJudgementUploadMainDocumentProcessor::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgement'],
    ),
)]
final readonly class ComplaintJudgementUploadMainDocumentRequestDto implements UploadMainDocumentRequestInterface
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
