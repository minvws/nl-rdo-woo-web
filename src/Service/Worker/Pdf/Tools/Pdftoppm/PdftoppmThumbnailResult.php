<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Tools\Pdftoppm;

final readonly class PdftoppmThumbnailResult
{
    /**
     * @param array<array-key,string|int> $params
     */
    public function __construct(
        public int $exitCode,
        public array $params,
        public ?string $errorMessage,
        public ?string $thumbnail,
    ) {
    }

    /**
     * @phpstan-assert-if-true !string $this->errorMessage
     * @phpstan-assert-if-true !null $this->thumbnail
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
     * @phpstan-assert-if-false !null $this->thumbnail
     */
    public function isFailed(): bool
    {
        return ! $this->isSuccessful();
    }
}
