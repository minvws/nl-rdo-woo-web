<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Organisation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Shared\Api\Publication\V1\PublicationV1Api;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}',
        ),
        new GetCollection(
            uriTemplate: '/organisation',
            uriVariables: [],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Organisation'],
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
            itemUriTemplate: '/organisation/{organisationId}',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(fromClass: self::class),
    ],
    routePrefix: PublicationV1Api::API_PREFIX,
    stateless: false,
    openapi: new Operation(
        tags: ['Organisation'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => [PublicationV1Api::API_TAG],
        ],
    ),
    provider: OrganisationProvider::class,
)]
final class OrganisationDto
{
    final public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
