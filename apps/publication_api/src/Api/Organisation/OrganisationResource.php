<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'Organisation',
    description: 'A government organisation that publishes dossiers via this API.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            name: 'get_organisation',
            output: OrganisationDetailResponseDto::class,
            openapi: new Operation(
                tags: ['Organisation'],
                summary: 'Retrieve an organisation',
                description: 'Retrieves a single organisation by its `organisationId`.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Organisation'],
                summary: 'Retrieve organisations',
                description: 'Retrieves a cursor-paginated list of all organisations. When more results are '
                    . 'available, follow the `next` link in the response to fetch the next page.',
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
