<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'Covenant',
    description: 'A covenant is a written set of agreements between a government organisation and one or more other parties.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_COVENANT,
            openapi: new Operation(
                tags: ['Covenant'],
                summary: 'Retrieve a covenant',
                description: 'Retrieves a single covenant, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Covenant'],
                summary: 'Retrieve covenants',
                description: 'Retrieves a cursor-paginated list of covenants for the organisation, newest first. When '
                    . 'more results are available, follow the `next` link in the response to fetch the next page.',
            ),
            paginationEnabled: false,
            name: 'get_covenants',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: CovenantRequestDto::class,
            read: false,
            name: 'update_covenant',
            openapi: new Operation(
                tags: ['Covenant'],
                summary: 'Create or replace a covenant',
                description: 'Creates a new covenant, or fully replaces the existing covenant identified by '
                    . '`dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Covenant'],
    ),
    output: CovenantResponseDto::class,
    provider: CovenantProvider::class,
    processor: CovenantProcessor::class,
)]
final class CovenantResource
{
    public const string ROUTE_NAME_GET_COVENANT = 'get_covenant';
}
