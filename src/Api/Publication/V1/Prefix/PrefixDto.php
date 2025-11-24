<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Prefix;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Api\Publication\V1\PublicationV1Api;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Prefix'],
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
            itemUriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            input: PrefixCreateDto::class,
            read: false,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'prefixId' => new Link(fromClass: self::class),
    ],
    routePrefix: PublicationV1Api::API_PREFIX,
    stateless: false,
    openapi: new Operation(
        tags: ['Prefix'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => ['publication-v1'],
        ],
    ),
    provider: PrefixProvider::class,
    processor: PrefixProcessor::class,
)]
final class PrefixDto
{
    final public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $prefix,
    ) {
    }
}
