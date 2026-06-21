<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'Advice',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_ADVICE,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Advice'],
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
            name: 'get_advices',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/advice/external/{dossierExternalId}',
            input: AdviceRequestDto::class,
            read: false,
            name: 'update_advice',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
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
