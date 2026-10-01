<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'DraftDecision',
    description: 'The drafts of laws and other regulations on which a government organisation has requested advice '
        . 'from an external party. The request for advice itself also falls under this category.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_DRAFT_DECISION,
            openapi: new Operation(
                tags: ['DraftDecision'],
                summary: 'Retrieve a draft decision',
                description: 'Retrieves a single draft decision, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['DraftDecision'],
                summary: 'Retrieve draft decisions',
                description: 'Retrieves a cursor-paginated list of draft decisions for the organisation, newest '
                    . 'first. When more results are available, follow the `next` link in the response to fetch the '
                    . 'next page.',
            ),
            paginationEnabled: false,
            name: 'get_draft_decisions',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/draft-decision/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: DraftDecisionRequestDto::class,
            read: false,
            name: 'update_draft_decision',
            openapi: new Operation(
                tags: ['DraftDecision'],
                summary: 'Create or replace a draft decision',
                description: 'Creates a new draft decision, or fully replaces the existing draft decision identified '
                    . 'by `dossierExternalId` if one already exists.',
            ),
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
