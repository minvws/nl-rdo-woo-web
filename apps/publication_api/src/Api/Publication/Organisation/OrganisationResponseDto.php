<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Organisation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Organisation',
    operations: [
        new Get(
            name: 'get_organisation',
            uriTemplate: '/organisation/{organisationId}',
        ),
        new GetCollection(
            name: 'get_organisations',
            uriTemplate: '/organisation',
            uriVariables: [],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Organisation'],
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
    provider: OrganisationProvider::class,
)]
final class OrganisationResponseDto
{
    final public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
