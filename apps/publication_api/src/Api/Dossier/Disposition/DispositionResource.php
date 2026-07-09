<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Disposition',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_DISPOSITION,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Disposition'],
            ),
            paginationEnabled: false,
            name: 'get_dispositions',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            input: DispositionRequestDto::class,
            read: false,
            name: 'update_disposition',
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Disposition'],
    ),
    output: DispositionResponseDto::class,
    provider: DispositionProvider::class,
    processor: DispositionProcessor::class,
)]
final class DispositionResource
{
    public const string ROUTE_NAME_GET_DISPOSITION = 'get_disposition';
}
