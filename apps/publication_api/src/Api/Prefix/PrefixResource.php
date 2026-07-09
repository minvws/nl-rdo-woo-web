<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Prefix',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
            name: 'get_prefix',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/prefix',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Prefix'],
            ),
            paginationEnabled: false,
            name: 'get_prefixes',
            itemUriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
            output: CursorPage::class,
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Prefix'],
    ),
    output: PrefixResponseDto::class,
    provider: PrefixProvider::class,
)]
final class PrefixResource
{
}
