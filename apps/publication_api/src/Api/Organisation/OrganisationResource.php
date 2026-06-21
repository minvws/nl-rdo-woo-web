<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    shortName: 'Organisation',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}',
            name: 'get_organisation',
        ),
        new GetCollection(
            uriTemplate: '/organisation',
            uriVariables: [],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Organisation'],
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
            name: 'get_organisations',
            itemUriTemplate: '/organisation/{organisationId}',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Organisation'],
    ),
    output: OrganisationDetailResponseDto::class,
    provider: OrganisationProvider::class,
)]
final class OrganisationResource
{
}
