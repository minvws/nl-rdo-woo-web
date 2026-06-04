<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'Covenant',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            name: 'get_covenant',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Covenant'],
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
            name: 'get_covenants',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/covenant/external/{dossierExternalId}',
            input: CovenantRequestDto::class,
            read: false,
            name: 'update_covenant',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Covenant'],
    ),
    provider: CovenantProvider::class,
    processor: CovenantProcessor::class,
)]
final class CovenantResource
{
}
