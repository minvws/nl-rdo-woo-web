<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools\Pdftk;

final readonly class PdftkPageCountResult
{
    /**
     * @param array<array-key,string|int> $params
     */
    public function __construct(
        public int $exitCode,
        public array $params,
        public ?string $errorMessage,
        public string $sourcePdf,
        public ?int $numberOfPages,
    ) {
    }

    /**
     * @phpstan-assert-if-true !string $this->errorMessage
     * @phpstan-assert-if-true !null $this->numberOfPages
     *
     * @phpstan-assert-if-false !null $this->errorMessage
     * @phpstan-assert-if-false !int $this->numberOfPages
     */
    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }

    /**
     * @phpstan-assert-if-true !null $this->errorMessage
     * @phpstan-assert-if-true !int $this->numberOfPages
     *
     * @phpstan-assert-if-false !string $this->errorMessage
     * @phpstan-assert-if-false !null $this->numberOfPages
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }
}
