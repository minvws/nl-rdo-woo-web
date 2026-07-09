<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'AnnualReport',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_ANNUAL_REPORT,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['AnnualReport'],
            ),
            paginationEnabled: false,
            name: 'get_annual_reports',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/annual-report/external/{dossierExternalId}',
            input: AnnualReportRequestDto::class,
            read: false,
            name: 'update_annual_report',
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
