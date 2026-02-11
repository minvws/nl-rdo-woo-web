<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use DateTimeImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\Dossier\DossierDtoInterface;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/E:{investigationReportExternalId}',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['InvestigationReport'],
                parameters: [
                    new Parameter(
                        name: 'pagination[cursor]',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: ['type' => 'string']
                    ),
                ],
            ),
            paginationEnabled: false,
            itemUriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/E:{investigationReportExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/E:{investigationReportExternalId}',
            input: InvestigationReportRequestDto::class,
            read: false,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'investigationReportExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
    ),
    provider: InvestigationReportProvider::class,
    processor: InvestigationReportProcessor::class,
)]
final class InvestigationReportDto implements DossierDtoInterface
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     */
    final public function __construct(
        public Uuid $id,
        public ?string $externalId,
        public OrganisationReferenceDto $organisation,
        public string $prefix,
        public string $dossierNumber,
        public string $internalReference,
        public ?string $title,
        public string $summary,
        public ?string $subject,
        public DepartmentReferenceDto $department,
        public ?DateTimeImmutable $publicationDate,
        public DossierStatus $status,
        public MainDocumentResponseDto $mainDocument,
        public array $attachments,
        public DateTimeImmutable $dossierDate,
    ) {
    }
}
