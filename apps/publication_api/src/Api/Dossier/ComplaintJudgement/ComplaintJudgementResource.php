<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'ComplaintJudgement',
    description: 'A complaint judgement is the formal letter a government organisation sends to the complainant in response to a complaint.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_COMPLAINT_JUDGEMENT,
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
                summary: 'Retrieve a complaint judgement',
                description: 'Retrieves a single complaint judgement, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
                summary: 'Retrieve complaint judgements',
                description: 'Retrieves a cursor-paginated list of complaint judgements for the organisation, newest '
                    . 'first. When more results are available, follow the `next` link in the response to fetch the '
                    . 'next page.',
            ),
            paginationEnabled: false,
            name: 'get_complaint_judgements',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/complaint-judgement/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: ComplaintJudgementRequestDto::class,
            read: false,
            name: 'update_complaint_judgement',
            openapi: new Operation(
                tags: ['ComplaintJudgement'],
                summary: 'Create or replace a complaint judgement',
                description: 'Creates a new complaint judgement, or fully replaces the existing complaint judgement '
                    . 'identified by `dossierExternalId` if one already exists.',
            ),
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
