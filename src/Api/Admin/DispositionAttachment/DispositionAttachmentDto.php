<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionAttachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;

#[ApiResource(
    uriTemplate: '/disposition-attachments/{attachmentId}',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new GetCollection(
            uriTemplate: '/disposition-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.read')",
            itemUriTemplate: '/disposition-attachments/{attachmentId}',
        ),
        new Post(
            uriTemplate: '/disposition-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.update')",
            input: DispositionAttachmentCreateDto::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: DispositionAttachmentUpdateDto::class,
        ),
        new Delete(
            security: "is_granted('AuthMatrix.dossier.update')",
        ),
    ],
    uriVariables: [
        'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
        'attachmentId' => new Link(fromClass: self::class),
    ],
    routePrefix: '/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['DispositionAttachment'],
    ),
    paginationEnabled: false,
    exceptionToStatus: [
        AttachmentNotFoundException::class => 404,
    ],
    provider: DispositionAttachmentProvider::class,
    processor: DispositionAttachmentProcessor::class,
)]
final readonly class DispositionAttachmentDto extends AttachmentDto
{
}
