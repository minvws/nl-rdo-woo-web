<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content;

interface FileReferenceInterface
{
    public function getPath(): string;

    public function hasPath(): bool;
}
