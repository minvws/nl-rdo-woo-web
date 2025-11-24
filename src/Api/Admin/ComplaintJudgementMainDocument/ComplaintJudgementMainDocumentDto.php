<?php

declare(strict_types=1);

namespace Shared\Api\Admin\ComplaintJudgementMainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\Attachment\AttachmentCreateDto;
use Shared\Api\Admin\Attachment\AttachmentUpdateDto;
use Shared\Api\Admin\Dossier\DossierReferenceDto;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use Shared\Domain\Publication\MainDocument\MainDocumentNotFoundException;

#[ApiResource(
    uriTemplate: '/complaint-judgement-document/{mainDocumentId}',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new GetCollection(
            uriTemplate: '/complaint-judgement-document',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.read')",
            itemUriTemplate: '/complaint-judgement-document/{mainDocumentId}',
        ),
        new Post(
            uriTemplate: '/complaint-judgement-document',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.update')",
            input: AttachmentCreateDto::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: AttachmentUpdateDto::class,
        ),
        new Delete(
            security: "is_granted('AuthMatrix.dossier.update')",
        ),
    ],
    uriVariables: [
        'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
        'mainDocumentId' => new Link(fromClass: self::class),
    ],
    routePrefix: '/balie/api/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['ComplaintJudgementDocument'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => ['admin'],
        ],
    ),
    exceptionToStatus: [
        MainDocumentAlreadyExistsException::class => 409,
        MainDocumentNotFoundException::class => 404,
        DossierWorkflowException::class => 405,
    ],
    provider: ComplaintJudgementMainDocumentProvider::class,
    processor: ComplaintJudgementMainDocumentProcessor::class,
)]
final readonly class ComplaintJudgementMainDocumentDto extends AbstractMainDocumentDto
{
}
