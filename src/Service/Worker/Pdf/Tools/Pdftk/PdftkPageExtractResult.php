<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftk;

final readonly class PdftkPageExtractResult
{
    /**
     * @param array<array-key,string|int> $params
     */
    public function __construct(
        public int $exitCode,
        public array $params,
        public ?string $errorMessage,
        public int $pageNr,
        public ?string $pageContents,
    ) {
    }

    /**
     * @phpstan-assert-if-true !string $this->errorMessage
     * @phpstan-assert-if-true !null $this->pageContents
     *
     * @phpstan-assert-if-false !null $this->errorMessage
     */
    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }

    /**
     * @phpstan-assert-if-true !null $this->errorMessage
     *
     * @phpstan-assert-if-false !string $this->errorMessage
     * @phpstan-assert-if-false !null $this->pageContents
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }
}
