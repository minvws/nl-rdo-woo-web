<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'DraftDecision',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_DRAFT_DECISION,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['DraftDecision'],
            ),
            paginationEnabled: false,
            name: 'get_draft_decisions',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            input: DraftDecisionRequestDto::class,
            read: false,
            name: 'update_draft_decision',
        ),
    ],
    stateless: false,
    security: "is_granted('DraftDecisionFeature')",
    securityMessage: 'feature is not enabled',
    openapi: new Operation(
        tags: ['DraftDecision'],
    ),
    output: DraftDecisionResponseDto::class,
    provider: DraftDecisionProvider::class,
    processor: DraftDecisionProcessor::class,
)]
final class DraftDecisionResource
{
    public const string ROUTE_NAME_GET_DRAFT_DECISION = 'get_draft_decision';
}
