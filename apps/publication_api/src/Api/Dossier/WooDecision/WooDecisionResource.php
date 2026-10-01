<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[ApiResource(
    shortName: 'WooDecision',
    description: 'A decision on a request for information under the Dutch Open Government Act (Wet open overheid, '
        . 'Woo), including the published documents and any exemption grounds applied.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_WOO_DECISION,
            openapi: new Operation(
                tags: ['WooDecision'],
                summary: 'Retrieve a Woo decision',
                description: 'Retrieves a single Woo decision, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['WooDecision'],
                summary: 'Retrieve Woo decisions',
                description: 'Retrieves a cursor-paginated list of Woo decisions for the organisation, newest first. '
                    . 'When more results are available, follow the `next` link in the response to fetch the next '
                    . 'page.',
            ),
            paginationEnabled: false,
            name: self::ROUTE_NAME_GET_WOO_DECISIONS,
            itemUriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/woo-decision/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: WooDecisionRequestDto::class,
            read: false,
            name: self::ROUTE_NAME_UPDATE_WOO_DECISION,
            openapi: new Operation(
                tags: ['WooDecision'],
                summary: 'Create or replace a Woo decision',
                description: 'Creates a new Woo decision, or fully replaces the existing Woo decision identified by '
                    . '`dossierExternalId` if one already exists.',
            ),
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
