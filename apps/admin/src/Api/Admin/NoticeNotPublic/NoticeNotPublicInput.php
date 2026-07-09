<?php

declare(strict_types=1);

namespace Admin\Api\Admin\NoticeNotPublic;

use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

final class NoticeNotPublicInput
{
    public ?string $documentName = null;

    #[Assert\NotBlank]
    public PlainDate $formalDate;

    /**
     * @var list<string> $grounds
     */
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
    ])]
    public array $grounds = [];

    public ?string $explanation = null;
}
