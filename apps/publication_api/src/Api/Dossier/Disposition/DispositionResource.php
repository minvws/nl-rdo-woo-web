<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'Disposition',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            name: 'get_disposition',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Disposition'],
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
            name: 'get_dispositions',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/disposition/external/{dossierExternalId}',
            input: DispositionRequestDto::class,
            read: false,
            name: 'update_disposition',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Disposition'],
    ),
    provider: DispositionProvider::class,
    processor: DispositionProcessor::class,
)]
final class DispositionResource
{
}
