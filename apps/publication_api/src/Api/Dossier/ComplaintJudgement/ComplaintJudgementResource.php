<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'ComplaintJudgement',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_COMPLAINT_JUDGEMENT,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
            ),
            paginationEnabled: false,
            name: 'get_complaint_judgements',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            input: ComplaintJudgementRequestDto::class,
            read: false,
            name: 'update_complaint_judgement',
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgement'],
    ),
    output: ComplaintJudgementResponseDto::class,
    provider: ComplaintJudgementProvider::class,
    processor: ComplaintJudgementProcessor::class,
)]
final class ComplaintJudgementResource
{
    public const string ROUTE_NAME_GET_COMPLAINT_JUDGEMENT = 'get_complaint_judgement';
}
