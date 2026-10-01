<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'AnnualReport',
    description: 'An annual plan describes in concrete terms what goals a government organisation aims to achieve in '
        . 'that year, how it plans to do so, and what resources (funding) it allocates for this. An annual report '
        . 'describes, in retrospect, what a government organisation actually did, achieved and spent in a given year.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_ANNUAL_REPORT,
            openapi: new Operation(
                tags: ['AnnualReport'],
                summary: 'Retrieve an annual report',
                description: 'Retrieves a single annual report, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['AnnualReport'],
                summary: 'Retrieve annual reports',
                description: 'Retrieves a cursor-paginated list of annual reports for the organisation, newest first. '
                    . 'When more results are available, follow the `next` link in the response to fetch the next '
                    . 'page.',
            ),
            paginationEnabled: false,
            name: 'get_annual_reports',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: AnnualReportRequestDto::class,
            read: false,
            name: 'update_annual_report',
            openapi: new Operation(
                tags: ['AnnualReport'],
                summary: 'Create or replace an annual report',
                description: 'Creates a new annual report, or fully replaces the existing annual report identified by '
                    . '`dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['AnnualReport'],
    ),
    output: AnnualReportResponseDto::class,
    provider: AnnualReportProvider::class,
    processor: AnnualReportProcessor::class,
)]
final class AnnualReportResource
{
    public const string ROUTE_NAME_GET_ANNUAL_REPORT = 'get_annual_report';
}
