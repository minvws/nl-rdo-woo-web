<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Attachment;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentRequestDto
{
    public \DateTimeImmutable $formalDate;
    /**
     * @var list<string>
     */
    #[Assert\All([
        new Assert\Type('string'),
    ])]
    public array $grounds = [];
    public string $internalReference = '';
    public AttachmentLanguage $language;
    public AttachmentType $type;
}
