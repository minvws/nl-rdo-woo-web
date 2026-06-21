<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'OtherPublication',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_OTHER_PUBLICATION,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['OtherPublication'],
                parameters: [
                    new Parameter(
                        name: 'pagination',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: [
                            'type' => 'object',
                            'properties' => [
                                'cursor' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        style: 'deepObject',
                    ),
                ],
            ),
            paginationEnabled: false,
            name: 'get_other_publications',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/other-publication/external/{dossierExternalId}',
            input: OtherPublicationRequestDto::class,
            read: false,
            name: 'update_other_publication',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
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
