<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use Symfony\Component\Uid\Uuid;

/**
 * @codeCoverageIgnore Currently hard to test due to chunked file handling, which will be removed in woo-3346
 */
class ReplaceDocumentCommand
{
    public function __construct(
        private readonly Uuid $dossierUuid,
        private readonly Uuid $documentUuid,
        private readonly string $remotePath,
        private readonly string $originalFilename,
        private readonly bool $chunked,
        private readonly string $chunkUuid,
        private readonly int $chunkCount,
    ) {
    }

    public function getDossierUuid(): Uuid
    {
        return $this->dossierUuid;
    }

    public function getDocumentUuid(): Uuid
    {
        return $this->documentUuid;
    }

    public function isChunked(): bool
    {
        return $this->chunked;
    }

    public function getChunkUuid(): string
    {
        return $this->chunkUuid;
    }

    public function getChunkCount(): int
    {
        return $this->chunkCount;
    }

    public function getRemotePath(): string
    {
        return $this->remotePath;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }
}
