<?php

declare(strict_types=1);

namespace App\ValueObject;

class TranslatableMessage
{
    public function __construct(
        private readonly string $message,
        /** @var array<string, string> */
        private readonly array $placeholders,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, string>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}
