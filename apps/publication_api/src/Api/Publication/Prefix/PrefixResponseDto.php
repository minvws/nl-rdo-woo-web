<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Prefix;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Prefix',
    operations: [
        new Get(
            name: 'get_prefix',
            uriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
        ),
        new GetCollection(
            name: 'get_prefixes',
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Prefix'],
                parameters: [
                    new Parameter(
                        name: 'pagination',
                        in: 'query',
                        style: 'deepObject',
                        description: 'The cursor to get the next page of results.',
                        schema: [
                            'type' => 'object',
                            'properties' => [
                                'cursor' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ),
                ],
            ),
            paginationEnabled: false,
            itemUriTemplate: '/organisation/{organisationId}/prefix/{prefixId}',
        ),
        new Post(
            name: 'create_prefix',
            uriTemplate: '/organisation/{organisationId}/prefix',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            input: PrefixCreateDto::class,
            read: false,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'prefixId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Prefix'],
    ),
    provider: PrefixProvider::class,
    processor: PrefixProcessor::class,
)]
final class PrefixResponseDto
{
    final public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $prefix,
    ) {
    }
}
