<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportAttachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;

#[ApiResource(
    uriTemplate: '/investigation-report-attachments/{attachmentId}',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new GetCollection(
            uriTemplate: '/investigation-report-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.read')",
            itemUriTemplate: '/investigation-report-attachments/{attachmentId}',
        ),
        new Post(
            uriTemplate: '/investigation-report-attachments',
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
        'attachmentId' => new Link(fromClass: self::class),
    ],
    routePrefix: '/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['InvestigationReportAttachment'],
    ),
    paginationEnabled: false,
    exceptionToStatus: [
        AttachmentNotFoundException::class => 404,
    ],
    provider: InvestigationReportAttachmentProvider::class,
    processor: InvestigationReportAttachmentProcessor::class,
)]
final readonly class InvestigationReportAttachmentDto extends AbstractAttachmentDto
{
}
