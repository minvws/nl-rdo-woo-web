<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Organisation',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}',
            name: 'get_organisation',
            output: OrganisationDetailResponseDto::class,
        ),
        new GetCollection(
            uriTemplate: '/organisation',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Organisation'],
            ),
            paginationEnabled: false,
            name: 'get_organisations',
            itemUriTemplate: '/organisation/{organisationId}',
            output: CursorPage::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Organisation'],
    ),
    provider: OrganisationProvider::class,
)]
final class OrganisationResource
{
}
