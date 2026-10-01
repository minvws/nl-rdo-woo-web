<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'Advice',
    description: 'A (un)solicited advice on a draft law or regulation, or on another subject.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_ADVICE,
            openapi: new Operation(
                tags: ['Advice'],
                summary: 'Retrieve an advice',
                description: 'Retrieves a single advice, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Advice'],
                summary: 'Retrieve advices',
                description: 'Retrieves a cursor-paginated list of advices for the organisation, newest first. When '
                    . 'more results are available, follow the `next` link in the response to fetch the next page.',
            ),
            paginationEnabled: false,
            name: 'get_advices',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: AdviceRequestDto::class,
            read: false,
            name: 'update_advice',
            openapi: new Operation(
                tags: ['Advice'],
                summary: 'Create or replace an advice',
                description: 'Creates a new advice, or fully replaces the existing advice identified by `dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Advice'],
    ),
    output: AdviceResponseDto::class,
    provider: AdviceProvider::class,
    processor: AdviceProcessor::class,
)]
final class AdviceResource
{
    public const string ROUTE_NAME_GET_ADVICE = 'get_advice';
}
