<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content;

interface FileReferenceInterface
{
    public function getPath(): string;

    public function hasPath(): bool;
}
