<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use Stringable;
use Webmozart\Assert\Assert;

/**
 * @implements Equatable<DocumentNumber>
 */
final readonly class DocumentNumber implements Equatable, Stringable
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromPublicationContextAndDocumentId(
        PublicationContext $publicationContext,
        DocumentId $documentId,
    ): self {
        return new self($publicationContext->toString() . '-' . $documentId->toString());
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equalTo(Equatable $other): bool
    {
        Assert::isInstanceOf($other, self::class);

        return $this->value === $other->toString();
    }
}
