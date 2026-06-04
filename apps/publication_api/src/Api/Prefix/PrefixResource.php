<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'Prefix',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
            name: 'get_prefix',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Prefix'],
                parameters: [
                    new Parameter(
                        name: 'pagination',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: [
                            'type' => 'object',
                            'properties' => [
                                'cursor' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        style: 'deepObject',
                    ),
                ],
            ),
            paginationEnabled: false,
            name: 'get_prefixes',
            itemUriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            input: PrefixCreateDto::class,
            read: false,
            name: 'create_prefix',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'prefixId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Prefix'],
    ),
    provider: PrefixProvider::class,
    processor: PrefixProcessor::class,
)]
final class PrefixResource
{
}
