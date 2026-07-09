<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'RequestForAdvice',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            name: self::ROUTE_NAME_GET_REQUEST_FOR_ADVICE,
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['RequestForAdvice'],
            ),
            paginationEnabled: false,
            name: 'get_request_for_advices',
            itemUriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            output: CursorPage::class,
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/dossiers/request-for-advice/external/{dossierExternalId}',
            input: RequestForAdviceRequestDto::class,
            read: false,
            name: 'update_request_for_advice',
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
