<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Attachment;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Uid\Uuid;

final readonly class AttachmentResponseDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        private Uuid $id,
        private AttachmentType $type,
        private AttachmentLanguage $language,
        private \DateTimeImmutable $formalDate,
        private string $internalReference,
        private array $grounds,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): AttachmentType
    {
        return $this->type;
    }

    public function getLanguage(): AttachmentLanguage
    {
        return $this->language;
    }

    public function getFormalDate(): \DateTimeImmutable
    {
        return $this->formalDate;
    }

    public function getInternalReference(): string
    {
        return $this->internalReference;
    }

    /**
     * @return list<string>
     */
    public function getGrounds(): array
    {
        return $this->grounds;
    }

    /**
     * @param array<AbstractAttachment> $entities
     *
     * @return array<self>
     */
    public static function fromEntities(array $entities): array
    {
        return array_map(fn (AbstractAttachment $entity) => self::fromEntity($entity), $entities);
    }

    public static function fromEntity(AbstractAttachment $entity): self
    {
        return new self(
            $entity->getId(),
            $entity->getType(),
            $entity->getLanguage(),
            $entity->getFormalDate(),
            $entity->getInternalReference(),
            $entity->getGrounds(),
        );
    }
}
