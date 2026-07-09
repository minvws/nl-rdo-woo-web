<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Covenant',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_COVENANT,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Covenant'],
            ),
            paginationEnabled: false,
            name: 'get_covenants',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            input: CovenantRequestDto::class,
            read: false,
            name: 'update_covenant',
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
