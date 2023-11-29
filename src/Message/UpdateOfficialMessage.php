<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\GovernmentOfficial;
use Symfony\Component\Uid\Uuid;

class UpdateOfficialMessage
{
    public function __construct(
        private readonly Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forGovermentOfficial(GovernmentOfficial $official): self
    {
        return new self($official->getId());
    }
}
