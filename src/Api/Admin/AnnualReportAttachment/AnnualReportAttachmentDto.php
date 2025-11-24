<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AnnualReportAttachment;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use Shared\Api\Admin\Attachment\AbstractAttachmentDto;
use Shared\Api\Admin\Attachment\AttachmentCreateDto;
use Shared\Api\Admin\Attachment\AttachmentUpdateDto;
use Shared\Api\Admin\Dossier\DossierReferenceDto;
use Shared\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;

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
    routePrefix: '/balie/api/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['AnnualReportAttachment'],
        extensionProperties: [
            OpenApiFactory::API_PLATFORM_TAG => ['admin'],
        ],
    ),
    paginationEnabled: false,
    exceptionToStatus: [
        AttachmentNotFoundException::class => 404,
    ],
    provider: AnnualReportAttachmentProvider::class,
    processor: AnnualReportAttachmentProcessor::class,
)]
final readonly class AnnualReportAttachmentDto extends AbstractAttachmentDto
{
}
