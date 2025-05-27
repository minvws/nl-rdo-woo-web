<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class TranslatableException extends \RuntimeException implements TranslatableInterface
{
    private readonly string $translationKey;

    /**
     * @param array<string, string> $placeholders
     */
    public function __construct(string $message, ?string $translationKey = null, private readonly array $placeholders = [])
    {
        if (! $translationKey) {
            $translationKey = $message;
        }

        $this->translationKey = $translationKey;

        parent::__construct($message);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * @return array<string, string>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            id: $this->getTranslationKey(),
            parameters: $this->getPlaceholders(),
            locale: $locale,
        );
    }
}
