<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use PublicationApi\Api\Pagination\CursorPage;

#[ApiResource(
    shortName: 'Subject',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            name: 'get_subject',
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/subject',
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Subject'],
            ),
            paginationEnabled: false,
            name: 'get_subjects',
            itemUriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            output: CursorPage::class,
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/subject',
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
