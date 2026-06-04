<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Attachment;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentCreateDto
{
    public PlainDate $formalDate;

    #[Assert\NotBlank(normalizer: 'trim')]
    public string $uploadUuid;

    #[Assert\NotBlank(normalizer: 'trim')]
    public AttachmentType $type;

    public string $internalReference = '';

    #[Assert\NotBlank()]
    public AttachmentLanguage $language;

    /** @var array<array-key,string> $grounds */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public array $grounds = [];
}
