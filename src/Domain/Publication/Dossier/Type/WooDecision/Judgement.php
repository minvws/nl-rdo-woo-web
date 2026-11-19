<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function in_array;
use function sprintf;
use function strtolower;
use function trim;

enum Judgement: string implements TranslatableInterface
{
    case PUBLIC = 'public';
    case PARTIAL_PUBLIC = 'partial_public';
    case ALREADY_PUBLIC = 'already_public';
    case NOT_PUBLIC = 'not_public';

    /**
     * @throws InvalidArgumentException
     */
    public static function fromString(string $input): self
    {
        return match (strtolower(trim($input))) {
            'openbaar' => self::PUBLIC,
            'deels openbaar' => self::PARTIAL_PUBLIC,
            'reeds openbaar' => self::ALREADY_PUBLIC,
            'niet openbaar' => self::NOT_PUBLIC,
            default => throw new InvalidArgumentException(sprintf("Invalid judgement value '%s'", $input)),
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
        return in_array($this, self::atLeastPartialPublicValues());
    }

    /**
     * @return array<array-key, Judgement>
     */
    public static function atLeastPartialPublicValues(): array
    {
        return [
            self::PARTIAL_PUBLIC,
            self::PUBLIC,
        ];
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('dossier.type.woo-decision.judgement.' . $this->value, locale: $locale);
    }
}
