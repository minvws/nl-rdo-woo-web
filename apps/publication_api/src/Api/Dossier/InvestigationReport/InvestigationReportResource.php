<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'InvestigationReport',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            name: 'get_investigation_report',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['InvestigationReport'],
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
            name: 'get_investigation_reports',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            input: InvestigationReportRequestDto::class,
            read: false,
            name: 'update_investigation_report',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'dossierExternalId' => new Link(fromClass: self::class, identifiers: ['externalId']),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
    ),
    provider: InvestigationReportProvider::class,
    processor: InvestigationReportProcessor::class,
)]
final class InvestigationReportResource
{
}
