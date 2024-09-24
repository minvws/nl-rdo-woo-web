<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\FacetValue;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TranslatedFacetValue implements FacetValueInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private string $key,
        public string $rawValue,
    ) {
    }

    public static function create(TranslatorInterface $translator, string $key, string $value): self
    {
        return new self($translator, $key, $value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->translator->trans(
            sprintf('public.documents.%s.%s', $this->key, $this->rawValue)
        );
    }

    public function getDescription(): string
    {
        return $this->translator->trans(
            sprintf('public.search.type_description.%s', $this->rawValue)
        );
    }
}
