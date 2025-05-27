<?php

declare(strict_types=1);

namespace App\Message;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Symfony\Component\Uid\Uuid;

abstract class AbstractInquiryMessage
{
    final public function __construct(protected Uuid $uuid)
    {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forInquiry(Inquiry $inquiry): static
    {
        return new static($inquiry->getId());
    }
}
