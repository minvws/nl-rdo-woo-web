<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Attachment;

use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class AttachmentUpdateDto
{
    public ?PlainDate $formalDate = null;

    #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
    public ?string $uploadUuid = null;

    public ?AttachmentType $type = null;

    public ?string $internalReference = null;

    public ?AttachmentLanguage $language = null;

    /** @var ?array<array-key,string> $grounds */
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public ?array $grounds = null;
}
