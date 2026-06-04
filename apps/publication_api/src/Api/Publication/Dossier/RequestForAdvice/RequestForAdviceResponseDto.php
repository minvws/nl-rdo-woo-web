<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
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
    shortName: 'RequestForAdvice',
    operations: [
        new Get(
            name: 'get_request_for_advice',
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/E:{dossierExternalId}',
        ),
        new GetCollection(
            name: 'get_request_for_advices',
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['RequestForAdvice'],
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
            itemUriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/E:{dossierExternalId}',
        ),
        new Put(
            name: 'update_request_for_advice',
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/E:{dossierExternalId}',
            input: RequestForAdviceRequestDto::class,
            read: false,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['RequestForAdvice'],
    ),
    provider: RequestForAdviceProvider::class,
    processor: RequestForAdviceProcessor::class,
)]
final class RequestForAdviceResponseDto
{
    /**
     * @param list<AttachmentResponseDto> $attachments
     * @param list<string> $advisoryBodies
     */
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
        public array $attachments,
        public PlainDate $dossierDate,
        public string $link,
        public array $advisoryBodies,
    ) {
    }
}
