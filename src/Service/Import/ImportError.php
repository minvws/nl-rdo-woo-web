<?php

declare(strict_types=1);

namespace Shared\Service\Import;

use Shared\Exception\Import\ImportException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ImportError implements TranslatableInterface
{
    /**
     * @param array<string, string> $placeholders
     */
    public function __construct(
        public string $message,
        public string $translationKey,
        public array $placeholders = [],
    ) {
    }

    public static function fromException(ImportException $importException): self
    {
        return new self(
            $importException->getMessage(),
            $importException->getMessageKey(),
            $importException->getMessageData(),
        );
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            id: $this->translationKey,
            parameters: $this->placeholders,
            locale: $locale,
        );
    }
}
