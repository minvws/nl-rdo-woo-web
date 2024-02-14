<?php

declare(strict_types=1);

namespace App\Exception;

abstract class TranslatableException extends \RuntimeException
{
    private readonly string $translationKey;

    /**
     * @var array<string, string>
     */
    private readonly array $placeholders;

    /**
     * @param array<string, string> $placeholders
     */
    public function __construct(string $message, ?string $translationKey = null, array $placeholders = [])
    {
        if (! $translationKey) {
            $translationKey = $message;
        }

        $this->translationKey = $translationKey;
        $this->placeholders = $placeholders;

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
}
