<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Symfony\Component\Uid\Uuid;

readonly class GenerateInquiryInventoryCommand
{
    public function __construct(
        private Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forInquiry(Inquiry $inquiry): self
    {
        return new self($inquiry->getId());
    }
}
