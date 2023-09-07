<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class ProcessDocumentMessage
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
        bool $chunked = false,
        string $chunkUuid = '',
        int $chunkCount = 0
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
