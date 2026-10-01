<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'OtherPublication',
    description: 'This document has been made public as part of the effort obligation under the Dutch Open Government Act (Wet open overheid).',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_OTHER_PUBLICATION,
            openapi: new Operation(
                tags: ['OtherPublication'],
                summary: 'Retrieve a dossier of this type',
                description: 'Retrieves a single other publication, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['OtherPublication'],
                summary: 'Retrieve other publications',
                description: 'Retrieves a cursor-paginated list of other publications for the organisation, newest '
                    . 'first. When more results are available, follow the `next` link in the response to fetch the '
                    . 'next page.',
            ),
            paginationEnabled: false,
            name: 'get_other_publications',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: OtherPublicationRequestDto::class,
            read: false,
            name: 'update_other_publication',
            openapi: new Operation(
                tags: ['OtherPublication'],
                summary: 'Create or replace a dossier of this type',
                description: 'Creates a new dossier of this type, or fully replaces the existing other publication '
                    . 'identified by `dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['OtherPublication'],
    ),
    output: OtherPublicationResponseDto::class,
    provider: OtherPublicationProvider::class,
    processor: OtherPublicationProcessor::class,
)]
final class OtherPublicationResource
{
    public const string ROUTE_NAME_GET_OTHER_PUBLICATION = 'get_other_publication';
}
