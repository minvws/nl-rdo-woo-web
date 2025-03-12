<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Command;

use Symfony\Component\Uid\Uuid;

/**
 * @codeCoverageIgnore Currently hard to test due to chunked file handling, which will be removed in woo-3346
 */
class ProcessDocumentCommand
{
    protected Uuid $dossierUuid;
    protected string $remotePath;
    protected bool $chunked;
    protected string $chunkUuid;
    protected int $chunkCount;
    protected string $originalFilename;

    public function __construct(
        Uuid $dossierUuid,
        string $remotePath,
        string $originalFilename,
        bool $chunked,
        string $chunkUuid,
        int $chunkCount,
    ) {
        $this->dossierUuid = $dossierUuid;
        $this->remotePath = $remotePath;
        $this->chunked = $chunked;
        $this->chunkUuid = $chunkUuid;
        $this->chunkCount = $chunkCount;
        $this->originalFilename = $originalFilename;
    }

    public function getDossierUuid(): Uuid
    {
        return $this->dossierUuid;
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
