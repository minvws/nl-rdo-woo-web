<?php

declare(strict_types=1);

namespace Admin\Api\Admin\AbstractMainDocument;

use Admin\Api\Admin\Dossier\DossierReferenceDto;
use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

abstract readonly class AbstractMainDocumentDto
{
    /**
     * @param array<array-key,string> $grounds
     */
    final public function __construct(
        public DossierReferenceDto $dossier,
        public Uuid $id,
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
        public DateTimeImmutable $formalDate,
        public string $type,
        public string $mimeType,
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
        $mimeType = $entity->getFileInfo()->getMimeType();
        Assert::notNull($mimeType);

        return new static(
            DossierReferenceDto::fromEntity($entity->getDossier()),
            $entity->getId(),
            $entity->getFileInfo()->getName() ?? '',
            $entity->getFormalDate(),
            $entity->getType()->value,
            $mimeType,
            $entity->getFileInfo()->getSize(),
            $entity->getInternalReference(),
            $entity->getLanguage()->value,
            $entity->getGrounds()
        );
    }
}
