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
use PublicationApi\Domain\OpenApi\PathParameter\OrganisationIdLink;
use PublicationApi\Domain\OpenApi\PathParameter\SubjectIdLink;

#[ApiResource(
    shortName: 'Subject',
    description: 'A subject used to categorise publications on the public website.',
    operations: [
        new Get(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'subjectId' => new SubjectIdLink(),
            ],
            name: 'get_subject',
            output: SubjectDetailResponseDto::class,
            openapi: new Operation(
                tags: ['Subject'],
                summary: 'Retrieve a subject',
                description: 'Retrieves a single subject by its `subjectId` within the given organisation.',
            ),
        ),
        new GetCollection(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']],
            openapi: new Operation(
                tags: ['Subject'],
                summary: 'Retrieve subjects',
                description: 'Retrieves a cursor-paginated list of subjects for the organisation. When more results '
                    . 'are available, follow the `next` link in the response to fetch the next page.',
            ),
            paginationEnabled: false,
            name: 'get_subjects',
            itemUriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            output: CursorPage::class,
        ),
        new Post(
            uriTemplate: '/organisation/{organisationId}/subject',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
            ],
            input: SubjectCreateDto::class,
            read: false,
            name: 'create_subject',
            output: SubjectDetailResponseDto::class,
            openapi: new Operation(
                tags: ['Subject'],
                summary: 'Create a subject',
                description: 'Creates a new subject for the organisation.',
            ),
        ),
        new Put(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'subjectId' => new SubjectIdLink(),
            ],
            input: SubjectUpdateDto::class,
            name: 'update_subject',
            output: SubjectDetailResponseDto::class,
            openapi: new Operation(
                tags: ['Subject'],
                summary: 'Update a subject',
                description: 'Updates the name and landing page of an existing subject.',
            ),
        ),
        new Delete(
            uriTemplate: '/organisation/{organisationId}/subject/{subjectId}',
            uriVariables: [
                'organisationId' => new OrganisationIdLink(),
                'subjectId' => new SubjectIdLink(),
            ],
            name: 'delete_subject',
            openapi: new Operation(
                tags: ['Subject'],
                summary: 'Delete a subject',
                description: 'Deletes a subject. Fails if the subject is still in use by a dossier.',
            ),
        ),
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['Subject'],
    ),
    output: SubjectResponseDto::class,
    provider: SubjectProvider::class,
    processor: SubjectProcessor::class,
)]
final class SubjectResource
{
}
