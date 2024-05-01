<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CovenantDocumentAlreadyExistsException;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument\CovenantDocumentNotFoundException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ApiResource(
    uriTemplate: '/dossiers/{dossierId}/covenant-document',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new Post(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: CovenantDocumentCreateDto::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: CovenantDocumentUpdateDto::class,
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
        tags: ['Covenant'],
    ),
    exceptionToStatus: [
        CovenantDocumentAlreadyExistsException::class => 409,
        CovenantDocumentNotFoundException::class => 404,
        DossierWorkflowException::class => 405,
    ],
    provider: CovenantDocumentProvider::class,
    processor: CovenantDocumentProcessor::class,
)]
final readonly class CovenantDocumentDto
{
    /**
     * @param string[] $grounds
     */
    public function __construct(
        public DossierReferenceDto $dossier,
        #[Context([DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'])]
        #[ApiProperty(
            openapiContext: [
                'type' => 'string',
                'format' => 'date',
            ],
            jsonSchemaContext: [
                'type' => 'string',
                'format' => 'date',
            ]
        )]
        public \DateTimeImmutable $formalDate,
        public string $internalReference,
        public string $language,
        public array $grounds,
        public string $name,
        public string $mimeType,
        public int $size,
    ) {
    }

    public static function fromEntity(CovenantDocument $entity): self
    {
        return new self(
            DossierReferenceDto::fromEntity($entity->getDossier()),
            $entity->getFormalDate(),
            $entity->getInternalReference(),
            $entity->getLanguage()->value,
            $entity->getGrounds(),
            $entity->getFileInfo()->getName() ?? '',
            $entity->getFileInfo()->getMimetype() ?? '',
            $entity->getFileInfo()->getSize(),
        );
    }
}
