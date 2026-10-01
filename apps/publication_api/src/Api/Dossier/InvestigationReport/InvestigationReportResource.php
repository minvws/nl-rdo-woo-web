<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'InvestigationReport',
    description: 'Government organisations regularly commission research, carried out by external researchers or by '
        . 'their own staff. An investigation report is characterised by a research question, which is answered based '
        . 'on an analysis of collected information or data.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_INVESTIGATION_REPORT,
            openapi: new Operation(
                tags: ['InvestigationReport'],
                summary: 'Retrieve an investigation report',
                description: 'Retrieves a single investigation report, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['InvestigationReport'],
                summary: 'Retrieve investigation reports',
                description: 'Retrieves a cursor-paginated list of investigation reports for the organisation, newest '
                    . 'first. When more results are available, follow the `next` link in the response to fetch the '
                    . 'next page.',
            ),
            paginationEnabled: false,
            name: 'get_investigation_reports',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: InvestigationReportRequestDto::class,
            read: false,
            name: 'update_investigation_report',
            openapi: new Operation(
                tags: ['InvestigationReport'],
                summary: 'Create or replace an investigation report',
                description: 'Creates a new investigation report, or fully replaces the existing investigation report '
                    . 'identified by `dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReport'],
    ),
    output: InvestigationReportResponseDto::class,
    provider: InvestigationReportProvider::class,
    processor: InvestigationReportProcessor::class,
)]
final class InvestigationReportResource
{
    public const string ROUTE_NAME_GET_INVESTIGATION_REPORT = 'get_investigation_report';
}
