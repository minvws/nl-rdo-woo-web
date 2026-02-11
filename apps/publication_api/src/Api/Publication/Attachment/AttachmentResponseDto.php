<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function array_values;

final readonly class AttachmentResponseDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public Uuid $id,
        #[ApiProperty(
            openapiContext: [
                'class' => 'AttachmentType',
            ],
        )]
        public AttachmentType $type,
        public AttachmentLanguage $language,
        public DateTimeImmutable $formalDate,
        public string $internalReference,
        public array $grounds,
        public ?string $fileName,
        public ?string $externalId,
    ) {
    }

    /**
     * @param array<array-key,AbstractAttachment> $entities
     *
     * @return list<self>
     */
    public static function fromEntities(array $entities): array
    {
        return array_values(array_map(self::fromEntity(...), $entities));
    }

    public static function fromEntity(AbstractAttachment $attachment): self
    {
        return new self(
            $attachment->getId(),
            $attachment->getType(),
            $attachment->getLanguage(),
            $attachment->getFormalDate(),
            $attachment->getInternalReference(),
            $attachment->getGrounds(),
            $attachment->getFileInfo()->getName(),
            $attachment->getExternalId()?->__toString(),
        );
    }
}
