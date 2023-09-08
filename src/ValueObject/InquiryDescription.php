<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Entity\Inquiry;

class InquiryDescription
{
    public function __construct(
        private readonly string $id,
        private readonly string $casenumber,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCasenumber(): string
    {
        return $this->casenumber;
    }

    public static function fromEntity(Inquiry $inquiry): self
    {
        return new self(
            $inquiry->getId()->toRfc4122(),
            $inquiry->getCasenr(),
        );
    }
}
