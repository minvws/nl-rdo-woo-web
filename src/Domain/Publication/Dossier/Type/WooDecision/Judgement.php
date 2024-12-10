<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Judgement: string implements TranslatableInterface
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

    public function isAlreadyPublic(): bool
    {
        return $this === self::ALREADY_PUBLIC;
    }

    public function isNotPublic(): bool
    {
        return $this === self::NOT_PUBLIC;
    }

    public function isAtLeastPartialPublic(): bool
    {
        return $this === self::PARTIAL_PUBLIC || $this === self::PUBLIC;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.woo-decision.judgement.' . $this->value, locale: $locale);
    }
}
