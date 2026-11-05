<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use Stringable;

use function strtolower;

final readonly class DocumentNumber implements Equatable, Stringable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        if ($value !== strtolower($value)) {
            throw new DocumentNumberException('Document number must be lowercase');
        }

        return new self($value);
    }

    public static function fromUnnormalizedString(string $value): self
    {
        return self::fromString(strtolower($value));
    }

    public static function fromPublicationContextAndDocumentId(
        PublicationContext $publicationContext,
        DocumentId $documentId,
    ): self {
        return self::fromString($publicationContext->toString() . '-' . $documentId->toString());
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equalTo(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
