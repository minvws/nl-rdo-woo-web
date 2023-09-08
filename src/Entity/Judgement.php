<?php

declare(strict_types=1);

namespace App\Entity;

enum Judgement: string
{
    case PUBLIC = 'public';
    case PARTIAL_PUBLIC = 'partial_public';
    case ALREADY_PUBLIC = 'already_public';
    case NOT_PUBLIC = 'not_public';

    public static function fromString(string $input): self
    {
        $input = strtolower(trim($input));

        return match ($input) {
            'openbaar' => self::PUBLIC,
            'deels openbaar' => self::PARTIAL_PUBLIC,
            'reeds openbaar' => self::ALREADY_PUBLIC,
            'niet openbaar' => self::NOT_PUBLIC,
            default => self::NOT_PUBLIC,
        };
    }

    public function isAtLeastPartialPublic(): bool
    {
        return $this === self::PARTIAL_PUBLIC || $this === self::PUBLIC;
    }
}
