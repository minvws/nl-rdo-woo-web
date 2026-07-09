<?php

declare(strict_types=1);

namespace Shared\Domain\Publication;

use DateTimeImmutable;
use Shared\Domain\HasId;

interface EntityWithFileInfo extends HasId
{
    public function getFileInfo(): FileInfo;

    public function setFileInfo(FileInfo $fileInfo): self;

    public function getFileCacheKey(): string;

    public function getUpdatedAt(): DateTimeImmutable;
}
