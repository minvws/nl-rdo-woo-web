<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Organisation\OrganisationResponseDto;

#[ApiResource(
    shortName: 'Subject',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            name: 'get_subject',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Subject'],
                parameters: [
                    new Parameter(
                        name: 'pagination',
                        in: 'query',
                        description: 'The cursor to get the next page of results.',
                        schema: [
                            'type' => 'object',
                            'properties' => [
                                'cursor' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        style: 'deepObject',
                    ),
                ],
            ),
            paginationEnabled: false,
            name: 'get_subjects',
            itemUriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
            ],
            input: SubjectCreateDto::class,
            read: false,
            name: 'create_subject',
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            input: SubjectUpdateDto::class,
            name: 'update_subject',
        ),
        new Delete(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            name: 'delete_subject',
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationResponseDto::class),
        'subjectId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Subject'],
    ),
    output: SubjectResponse::class,
    provider: SubjectProvider::class,
    processor: SubjectProcessor::class,
)]
final class SubjectResource
{
}
