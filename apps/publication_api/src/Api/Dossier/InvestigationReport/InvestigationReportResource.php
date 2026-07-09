<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'InvestigationReport',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_INVESTIGATION_REPORT,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['InvestigationReport'],
            ),
            paginationEnabled: false,
            name: 'get_investigation_reports',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/investigation-report/external/{dossierExternalId}',
            input: InvestigationReportRequestDto::class,
            read: false,
            name: 'update_investigation_report',
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
