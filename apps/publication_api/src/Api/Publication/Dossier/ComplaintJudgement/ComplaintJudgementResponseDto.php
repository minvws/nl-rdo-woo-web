<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'ComplaintJudgement',
    operations: [
        new Get(
            name: 'get_complaint_judgement',
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/E:{dossierExternalId}',
        ),
        new GetCollection(
            name: 'get_complaint_judgements',
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
                parameters: [
                    new Parameter(
                        name: 'pagination',
                        in: 'query',
                        style: 'deepObject',
                        description: 'The cursor to get the next page of results.',
                        schema: [
                            'type' => 'object',
                            'properties' => [
                                'cursor' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ),
                ],
            ),
            paginationEnabled: false,
            itemUriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/E:{dossierExternalId}',
        ),
        new Put(
            name: 'update_complaint_judgement',
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/E:{dossierExternalId}',
            input: ComplaintJudgementRequestDto::class,
            read: false,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgement'],
    ),
    provider: ComplaintJudgementProvider::class,
    processor: ComplaintJudgementProcessor::class,
)]
final class ComplaintJudgementResponseDto
{
    final public function __construct(
        public Uuid $id,
        public ?ExternalId $externalId,
        public OrganisationReferenceDto $organisation,
        public string $dossierNumber,
        public ?string $title,
        public string $summary,
        public ?string $subject,
        public DepartmentReferenceDto $department,
        #[ApiProperty(openapiContext: [
            'type' => 'string',
            'format' => 'date',
            'example' => '2025-12-21',
            'nullable' => true,
        ])]
        #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        public ?PlainDate $publicationDate,
        public DossierStatus $status,
        public MainDocumentResponseDto $mainDocument,
        public PlainDate $dossierDate,
    ) {
    }
}
