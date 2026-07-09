<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ApiResource(
    shortName: 'WooDecision',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_WOO_DECISION,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['WooDecision'],
            ),
            paginationEnabled: false,
            name: self::ROUTE_NAME_GET_WOO_DECISIONS,
            itemUriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            input: WooDecisionRequestDto::class,
            read: false,
            name: self::ROUTE_NAME_UPDATE_WOO_DECISION,
        ),
    ],
    stateless: false,
    normalizationContext: [
        AbstractObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
    ],
    openapi: new Operation(
        tags: ['WooDecision'],
    ),
    output: WooDecisionResponseDto::class,
    provider: WooDecisionProvider::class,
    processor: WooDecisionProcessor::class,
)]
final class WooDecisionResource
{
    public const string ROUTE_NAME_GET_WOO_DECISION = 'get_woo_decision';
    public const string ROUTE_NAME_GET_WOO_DECISIONS = 'get_woo_decisions';
    public const string ROUTE_NAME_UPDATE_WOO_DECISION = 'update_woo_decision';
}
