<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'Disposition',
    description: 'A disposition is a formal, written decision by a government organisation. Dispositions concern '
        . 'specific, individual cases (for example a company, a resident, a violation) — they do not apply to '
        . 'everyone.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_DISPOSITION,
            openapi: new Operation(
                tags: ['Disposition'],
                summary: 'Retrieve a disposition',
                description: 'Retrieves a single disposition, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Disposition'],
                summary: 'Retrieve dispositions',
                description: 'Retrieves a cursor-paginated list of dispositions for the organisation, newest first. '
                    . 'When more results are available, follow the `next` link in the response to fetch the next '
                    . 'page.',
            ),
            paginationEnabled: false,
            name: 'get_dispositions',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: DispositionRequestDto::class,
            read: false,
            name: 'update_disposition',
            openapi: new Operation(
                tags: ['Disposition'],
                summary: 'Create or replace a disposition',
                description: 'Creates a new disposition, or fully replaces the existing disposition identified by '
                    . '`dossierExternalId` if one already exists.',
            ),
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
