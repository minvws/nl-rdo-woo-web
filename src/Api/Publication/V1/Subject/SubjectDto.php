<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Subject;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Api\Publication\V1\PublicationV1Api;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Subject'],
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
            itemUriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            input: SubjectCreateDto::class,
            read: false,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            input: SubjectUpdateDto::class,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'subjectId' => new Link(fromClass: self::class),
    ],
    routePrefix: PublicationV1Api::API_PREFIX,
    stateless: false,
    openapi: new Operation(
        tags: ['Subject'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => [PublicationV1Api::API_TAG],
        ],
    ),
    provider: SubjectProvider::class,
    processor: SubjectProcessor::class,
)]
final class SubjectDto
{
    final public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $name,
    ) {
    }
}
