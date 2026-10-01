<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Domain\OpenApi\PathParameter\DossierExternalIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;

#[ApiResource(
    shortName: 'RequestForAdvice',
    description: 'The request for advice on a draft law or regulation, or on another subject.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            name: self::ROUTE_NAME_GET_REQUEST_FOR_ADVICE,
            openapi: new Operation(
                tags: ['RequestForAdvice'],
                summary: 'Retrieve a request for advice',
                description: 'Retrieves a single request for advice, identified by `dossierExternalId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['RequestForAdvice'],
                summary: 'Retrieve requests for advice',
                description: 'Retrieves a cursor-paginated list of requests for advice for the organisation, newest '
                    . 'first. When more results are available, follow the `next` link in the response to fetch the '
                    . 'next page.',
            ),
            paginationEnabled: false,
            name: 'get_request_for_advices',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'dossierExternalId' => new DossierExternalIdLink(),
            ],
            input: RequestForAdviceRequestDto::class,
            read: false,
            name: 'update_request_for_advice',
            openapi: new Operation(
                tags: ['RequestForAdvice'],
                summary: 'Create or replace a request for advice',
                description: 'Creates a new request for advice, or fully replaces the existing request for advice '
                    . 'identified by `dossierExternalId` if one already exists.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['RequestForAdvice'],
    ),
    output: RequestForAdviceResponseDto::class,
    provider: RequestForAdviceProvider::class,
    processor: RequestForAdviceProcessor::class,
)]
final class RequestForAdviceResource
{
    public const string ROUTE_NAME_GET_REQUEST_FOR_ADVICE = 'get_request_for_advice';
}
