<?php

declare(strict_types=1);

namespace App\Api\Admin\AbstractMainDocument;

use ApiPlatform\Metadata\ApiProperty;
use App\Api\Admin\Dossier\DossierReferenceDto;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
abstract readonly class AbstractMainDocumentDto
{
    /**
     * @param array<array-key,string> $grounds
     */
    final public function __construct(
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
        #[ApiProperty(
            openapiContext: [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
            jsonSchemaContext: [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ]
        )]
        public array $grounds,
    ) {
    }

    public static function fromEntity(AbstractMainDocument $entity): static
    {
        return new static(
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
