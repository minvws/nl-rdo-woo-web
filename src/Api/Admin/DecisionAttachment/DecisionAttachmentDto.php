<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\DecisionAttachmentNotFoundException;
use App\Entity\DecisionAttachment;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
#[ApiResource(
    uriTemplate: '/decision-attachments/{decisionAttachmentId}',
    operations: [
        new Get(
            security: "is_granted('AuthMatrix.dossier.read')",
        ),
        new GetCollection(
            uriTemplate: '/decision-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.read')",
            itemUriTemplate: '/decision-attachments/{decisionAttachmentId}',
        ),
        new Post(
            uriTemplate: '/decision-attachments',
            uriVariables: [
                'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
            ],
            security: "is_granted('AuthMatrix.dossier.update')",
            input: DecisionAttachmentCreateDto::class,
        ),
        new Put(
            security: "is_granted('AuthMatrix.dossier.update')",
            input: DecisionAttachmentUpdateDto::class,
        ),
        new Delete(
            security: "is_granted('AuthMatrix.dossier.update')",
        ),
    ],
    uriVariables: [
        'dossierId' => new Link(toProperty: 'dossier', fromClass: DossierReferenceDto::class),
        'decisionAttachmentId' => new Link(fromClass: DecisionAttachmentDto::class),
    ],
    routePrefix: '/dossiers/{dossierId}',
    stateless: false,
    openapi: new Operation(
        tags: ['DecisionAttachment'],
    ),
    paginationEnabled: false,
    exceptionToStatus: [
        DecisionAttachmentNotFoundException::class => 404,
    ],
    provider: DecisionAttachmentProvider::class,
    processor: DecisionAttachmentProcessor::class,
)]
final readonly class DecisionAttachmentDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        #[ApiProperty(writable: false, identifier: true, genId: false)]
        public string $id,
        public DossierReferenceDto $dossier,
        public string $name,
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
        public string $type,
        public ?string $mimeType,
        public int $size,
        public string $internalReference,
        public string $language,
        public array $grounds,
    ) {
    }

    public static function fromEntity(DecisionAttachment $entity): self
    {
        return new self(
            $entity->getId()->toRfc4122(),
            DossierReferenceDto::fromEntity($entity->getDossier()),
            $entity->getFileInfo()->getName() ?? '',
            $entity->getFormalDate(),
            $entity->getType()->value,
            $entity->getFileInfo()->getMimeType(),
            $entity->getFileInfo()->getSize(),
            $entity->getInternalReference(),
            $entity->getLanguage()->value,
            $entity->getGrounds()
        );
    }
}
