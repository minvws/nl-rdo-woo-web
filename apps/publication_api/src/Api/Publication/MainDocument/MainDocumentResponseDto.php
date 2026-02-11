<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\MainDocument;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Uid\Uuid;

final readonly class MainDocumentResponseDto
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
    ) {
    }

    public static function fromEntity(AbstractMainDocument $entity): self
    {
        return new self(
            $entity->getId(),
            $entity->getType(),
            $entity->getLanguage(),
            $entity->getFormalDate(),
            $entity->getInternalReference(),
            $entity->getGrounds(),
            $entity->getFileInfo()->getName(),
        );
    }
}
