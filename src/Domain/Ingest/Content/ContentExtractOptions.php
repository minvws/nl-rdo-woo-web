<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

use Shared\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;

use function in_array;

class ContentExtractOptions
{
    /**
     * @var array<array-key,ContentExtractorKey>
     */
    private array $enabledExtractors = [];

    private ?int $pageNumber = null;

    private ?string $fileContents = null;

    public static function create(): self
    {
        return new self();
    }

    public function withExtractor(ContentExtractorKey $key): self
    {
        // Set with the value as key so duplicates overwrite
        $this->enabledExtractors[$key->value] = $key;

        return $this;
    }

    public function withAllExtractors(): self
    {
        foreach (ContentExtractorKey::cases() as $key) {
            $this->withExtractor($key);
        }

        return $this;
    }

    /**
     * @return array<array-key,ContentExtractorKey>
     */
    public function getEnabledExtractors(): array
    {
        return $this->enabledExtractors;
    }

    public function isExtractorEnabled(ContentExtractorInterface $extractor): bool
    {
        return in_array($extractor->getKey(), $this->enabledExtractors, true);
    }

    public function withPageNumber(?int $pageNumber): self
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }

    /** @phpstan-assert-if-true !null $this->getPageNumber() */
    public function hasPageNumber(): bool
    {
        return $this->pageNumber !== null;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function withFileContents(?string $fileContents): self
    {
        $this->fileContents = $fileContents;

        return $this;
    }

    public function hasFileContents(): bool
    {
        return $this->fileContents !== null;
    }

    public function getFileContents(): ?string
    {
        return $this->fileContents;
    }
}
