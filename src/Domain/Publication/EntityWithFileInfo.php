<?php

declare(strict_types=1);

namespace Shared\Domain\Publication;

use Symfony\Component\Uid\Uuid;

interface EntityWithFileInfo
{
    public function getId(): Uuid;

    public function getFileInfo(): FileInfo;

    public function setFileInfo(FileInfo $fileInfo): self;

    public function getFileCacheKey(): string;

    public function getUpdatedAt(): \DateTimeImmutable;
}
