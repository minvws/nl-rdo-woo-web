<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Attachment;

use ApiPlatform\Metadata\ApiProperty;
use DateTimeImmutable;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentRequestDto
{
    /**
     * @param array<array-key,string> $grounds
     */
    public function __construct(
        public string $fileName,
        public DateTimeImmutable $formalDate,
        #[Assert\All([
            new Assert\Type('string'),
        ])]
        public AttachmentLanguage $language,
        #[ApiProperty(
            openapiContext: [
                'class' => 'AttachmentType',
            ],
        )]
        public AttachmentType $type,
        public string $externalId,
        public array $grounds = [],
        public string $internalReference = '',
    ) {
    }
}
