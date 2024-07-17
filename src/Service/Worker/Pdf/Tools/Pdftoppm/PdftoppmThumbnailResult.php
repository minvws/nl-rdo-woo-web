<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools\Pdftoppm;

final readonly class PdftoppmThumbnailResult
{
    /**
     * @param array<array-key,string|int> $params
     */
    public function __construct(
        public int $exitCode,
        public array $params,
        public ?string $errorMessage,
        public string $sourcePdf,
        public string $targetPath,
    ) {
    }

    /**
     * @phpstan-assert-if-true !string $this->errorMessage
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
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }
}
