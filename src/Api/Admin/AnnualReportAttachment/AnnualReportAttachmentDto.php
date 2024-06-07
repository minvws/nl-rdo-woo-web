<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportAttachment;

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
    uriTemplate: '/annual-report-attachments/{attachmentId}',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new GetCollection(
            uriTemplate: '/annual-report-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.read')",
            itemUriTemplate: '/annual-report-attachments/{attachmentId}',
        ),
        new Post(
            uriTemplate: '/annual-report-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.update')",
            input: AnnualReportAttachmentCreateDto::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: AnnualReportAttachmentUpdateDto::class,
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
        tags: ['AnnualReportAttachment'],
    ),
    paginationEnabled: false,
    exceptionToStatus: [
        AttachmentNotFoundException::class => 404,
    ],
    provider: AnnualReportAttachmentProvider::class,
    processor: AnnualReportAttachmentProcessor::class,
)]
final readonly class AnnualReportAttachmentDto extends AttachmentDto
{
}
