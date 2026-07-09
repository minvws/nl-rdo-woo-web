<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Department',
    operations: [
        new Get(
            uriTemplate: '/department/{departmentId}',
            name: 'get_department',
            output: DepartmentDetailResponseDto::class,
        ),
        new GetCollection(
            uriTemplate: '/department',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Department'],
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
