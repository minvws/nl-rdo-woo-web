<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;

#[ApiResource(
    shortName: 'Department',
    operations: [
        new Get(
            uriTemplate: '/department/{departmentId}',
            name: 'get_department',
        ),
        new GetCollection(
            uriTemplate: '/department',
            uriVariables: [],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Department'],
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
            name: 'get_departments',
            itemUriTemplate: '/department/{departmentId}',
        ),
    ],
    uriVariables: [
        'departmentId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Department'],
    ),
    provider: DepartmentProvider::class,
)]
final class DepartmentResource
{
}
