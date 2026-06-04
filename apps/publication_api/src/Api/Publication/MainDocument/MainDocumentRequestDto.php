<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\MainDocument;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class MainDocumentRequestDto
{
    /**
     * @param list<string> $grounds
     */
    public function __construct(
        public string $fileName,
        public PlainDate $formalDate,
        public AttachmentLanguage $language,
        public AttachmentType $type,
        #[Assert\All([
            new Assert\Choice(choices: Citation::ALL_GROUND_KEYS),
        ])]
        public array $grounds = [],
    ) {
    }
}
