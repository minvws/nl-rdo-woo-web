<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DepartmentIdLink;

#[ApiResource(
    shortName: 'Department',
    description: 'A department within an organisation that dossiers can be attributed to.',
    operations: [
        new Get(
            uriTemplate: '/department/{departmentId}',
            uriVariables: [
                'departmentId' => new DepartmentIdLink(),
            ],
            name: 'get_department',
            output: DepartmentDetailResponseDto::class,
            openapi: new Operation(
                tags: ['Department'],
                summary: 'Retrieve a department',
                description: 'Retrieves a single department by its `departmentId`.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/department',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Department'],
                summary: 'Retrieve departments',
                description: 'Retrieves a cursor-paginated list of all departments. When more results are available, '
                    . 'follow the `next` link in the response to fetch the next page.',
            ),
            paginationEnabled: false,
            name: 'get_departments',
            itemUriTemplate: '/department/{departmentId}',
            output: CursorPage::class,
        ),
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
