<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Shared\Api\Publication\V1\Attachment\AttachmentResponseDto;
use Shared\Api\Publication\V1\Department\DepartmentReferenceDto;
use Shared\Api\Publication\V1\Dossier\AbstractDossierDto;
use Shared\Api\Publication\V1\MainDocument\MainDocumentResponseDto;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Api\Publication\V1\PublicationV1Api;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/{otherPublicationId}',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['OtherPublication'],
                parameters: [
                    new Parameter(
                        name: 'pagination[cursor]',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: ['type' => 'string']
                    ),
                ],
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_TAG => [PublicationV1Api::API_TAG],
                ],
            ),
            paginationEnabled: false,
            itemUriTemplate: '/organisation/{organisationId}/dossiers/other-publication/{otherPublicationId}',
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            input: OtherPublicationCreateRequestDto::class,
            read: false,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/{otherPublicationId}',
            input: OtherPublicationUpdateRequestDto::class,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'otherPublicationId' => new Link(fromClass: self::class),
    ],
    routePrefix: PublicationV1Api::API_PREFIX,
    stateless: false,
    openapi: new Operation(
        tags: ['OtherPublication'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => [PublicationV1Api::API_TAG],
        ],
    ),
    provider: OtherPublicationProvider::class,
    processor: OtherPublicationProcessor::class,
)]
final class OtherPublicationDto extends AbstractDossierDto
{
    /**
     * @param array<AttachmentResponseDto> $attachments
     */
    final public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $prefix,
        public string $dossierNumber,
        public string $internalReference,
        public ?string $title,
        public string $summary,
        public ?string $subject,
        public DepartmentReferenceDto $department,
        public ?\DateTimeImmutable $publicationDate,
        public DossierStatus $status,
        public MainDocumentResponseDto $mainDocument,
        public array $attachments,
        public \DateTimeImmutable $dossierDate,
    ) {
        parent::__construct(
            $id,
            $organisation,
            $prefix,
            $dossierNumber,
            $internalReference,
            $title,
            $summary,
            $subject,
            $publicationDate,
            $status,
        );
    }
}
