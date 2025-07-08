<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;

class ContentExtractOptions
{
    private bool $refresh = false;

    /**
     * @var array<array-key,ContentExtractorKey>
     */
    private array $enabledExtractors = [];

    private ?int $pageNumber = null;

    private ?string $localFile = null;

    public static function create(): self
    {
        return new self();
    }

    public function withRefresh(bool $refresh = true): self
    {
        $this->refresh = $refresh;

        return $this;
    }

    public function hasRefresh(): bool
    {
        return $this->refresh;
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

    public function withoutPageNumber(): self
    {
        $clone = clone $this;
        $clone->pageNumber = null;

        return $clone;
    }

    public function withLocalFile(?string $localFile): self
    {
        $this->localFile = $localFile;

        return $this;
    }

    public function hasLocalFile(): bool
    {
        return $this->localFile !== null;
    }

    public function getLocalFile(): ?string
    {
        return $this->localFile;
    }
}
