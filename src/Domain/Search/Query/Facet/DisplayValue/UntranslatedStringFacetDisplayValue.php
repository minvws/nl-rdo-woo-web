<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\DisplayValue;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class UntranslatedStringFacetDisplayValue implements FacetDisplayValueInterface
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = trim($value);

        return new self($value);
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->value;
    }
}
