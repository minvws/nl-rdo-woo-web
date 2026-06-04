<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Subject;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Subject',
    operations: [
        new Get(
            name: 'get_subject',
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
        ),
        new GetCollection(
            name: 'get_subjects',
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Subject'],
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
            itemUriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
        ),
        new Post(
            name: 'create_subject',
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
            ],
            input: SubjectCreateDto::class,
            read: false,
        ),
        new Put(
            name: 'update_subject',
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            input: SubjectUpdateDto::class,
        ),
    ],
    uriVariables: [
        'organisationId' => new Link(toProperty: 'organisation', fromClass: OrganisationReferenceDto::class),
        'subjectId' => new Link(fromClass: self::class),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Subject'],
    ),
    provider: SubjectProvider::class,
    processor: SubjectProcessor::class,
)]
final class SubjectResponse
{
    final public function __construct(
        public Uuid $id,
        public OrganisationReferenceDto $organisation,
        public string $name,
    ) {
    }
}
