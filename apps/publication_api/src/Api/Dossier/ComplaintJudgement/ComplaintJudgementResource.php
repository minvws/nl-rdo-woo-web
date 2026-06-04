<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'ComplaintJudgement',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            name: 'get_complaint_judgement',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
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
            name: 'get_complaint_judgements',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            input: ComplaintJudgementRequestDto::class,
            read: false,
            name: 'update_complaint_judgement',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgement'],
    ),
    provider: ComplaintJudgementProvider::class,
    processor: ComplaintJudgementProcessor::class,
)]
final class ComplaintJudgementResource
{
}
