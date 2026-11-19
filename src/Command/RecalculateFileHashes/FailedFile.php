<?php

declare(strict_types=1);

namespace Shared\Command\RecalculateFileHashes;

use Symfony\Component\Uid\Uuid;

final readonly class FailedFile
{
    public function __construct(
        public string $type,
        public Uuid $id,
        public ?Uuid $dossierId,
        public ?string $documentPrefix,
        public ?string $fileName,
        public ?string $storagePath,
        public string $reason,
    ) {
    }
}
