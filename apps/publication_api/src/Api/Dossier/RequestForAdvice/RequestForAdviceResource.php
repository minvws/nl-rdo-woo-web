<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'RequestForAdvice',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            name: 'get_request_for_advice',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['RequestForAdvice'],
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
            name: 'get_request_for_advices',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            input: RequestForAdviceRequestDto::class,
            read: false,
            name: 'update_request_for_advice',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['RequestForAdvice'],
    ),
    provider: RequestForAdviceProvider::class,
    processor: RequestForAdviceProcessor::class,
)]
final class RequestForAdviceResource
{
}
