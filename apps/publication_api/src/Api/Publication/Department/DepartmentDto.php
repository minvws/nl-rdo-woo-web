<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Department;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/department/{departmentId}',
        ),
        new GetCollection(
            uriTemplate: '/department',
            uriVariables: [],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Department'],
                parameters: [
                    new Parameter(
                        name: 'pagination[cursor]',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: ['type' => 'string']
                    ),
                ],
            ),
            paginationEnabled: false,
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
final class DepartmentDto
{
    final public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
