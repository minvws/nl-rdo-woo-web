<?php

declare(strict_types=1);

namespace App\Api\Admin\AdviceMainDocument;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use App\Domain\Publication\MainDocument\MainDocumentNotFoundException;

#[ApiResource(
    uriTemplate: '/dossiers/{dossierId}/advice-document',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new Post(
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
    ],
    stateless: false,
    openapi: new Operation(
        tags: ['AdviceDocument'],
    ),
    exceptionToStatus: [
        MainDocumentAlreadyExistsException::class => 409,
        MainDocumentNotFoundException::class => 404,
        DossierWorkflowException::class => 405,
    ],
    provider: AdviceMainDocumentProvider::class,
    processor: AdviceMainDocumentProcessor::class,
)]
final readonly class AdviceMainDocumentDto extends AbstractMainDocumentDto
{
}
