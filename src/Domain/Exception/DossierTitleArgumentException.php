<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

use InvalidArgumentException;

/**
 * @phpstan-type ParameterMap array<string, int|string>
 */
final class DossierTitleArgumentException extends InvalidArgumentException
{
    /**
     * @param array<string, int|string> $parameters
     */
    public function __construct(
        private readonly string $translationKey,
        private readonly array $parameters = [],
    ) {
        parent::__construct($translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
