<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet\Input;

use RuntimeException;

use function count;
use function explode;
use function trim;

readonly class DocTypeValue
{
    private function __construct(
        private string $mainType,
        private ?string $subType,
    ) {
    }

    public static function fromString(string $rawValue): self
    {
        $rawValue = trim($rawValue);

        $valueParts = explode('.', $rawValue);
        if (count($valueParts) > 2) {
            throw new RuntimeException('Unexpected format for DocType: ' . $rawValue);
        }

        return new self(
            $valueParts[0],
            $valueParts[1] ?? null,
        );
    }

    public function getMainType(): string
    {
        return $this->mainType;
    }

    public function getSubType(): ?string
    {
        return $this->subType;
    }
}
