<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\DisplayValue;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslatedFacetDisplayValue implements FacetDisplayValueInterface
{
    public function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        return new self($value);
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, locale: $locale);
    }
}
