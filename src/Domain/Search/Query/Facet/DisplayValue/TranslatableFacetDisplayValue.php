<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\DisplayValue;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslatableFacetDisplayValue implements FacetDisplayValueInterface
{
    private function __construct(
        private TranslatableInterface $value,
    ) {
    }

    public static function fromTranslatable(TranslatableInterface $value): self
    {
        return new self($value);
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->value->trans($translator, locale: $locale);
    }
}
