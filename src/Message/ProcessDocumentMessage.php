<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Uid\Uuid;

class ProcessDocumentMessage
{
    // @todo: Rename to $dossierUuid, because this suggest the document uuid
    protected Uuid $uuid;
    protected string $remotePath;
    protected bool $chunked;
    protected string $chunkUuid;
    protected int $chunkCount;
    protected string $originalFilename;

    public function __construct(
        Uuid $uuid,
        string $remotePath,
        string $originalFilename,
        bool $chunked = false,
        string $chunkUuid = '',
        int $chunkCount = 0
    ) {
        $this->uuid = $uuid;
        $this->remotePath = $remotePath;
        $this->chunked = $chunked;
        $this->chunkUuid = $chunkUuid;
        $this->chunkCount = $chunkCount;
        $this->originalFilename = $originalFilename;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
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
