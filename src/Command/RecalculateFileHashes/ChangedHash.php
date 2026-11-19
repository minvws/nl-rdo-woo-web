<?php

declare(strict_types=1);

namespace Shared\Command\RecalculateFileHashes;

use Symfony\Component\Uid\Uuid;

final readonly class ChangedHash
{
    public function __construct(
        public string $type,
        public Uuid $id,
        public ?string $documentPrefix,
        public ?string $oldHash,
        public string $newHash,
    ) {
    }
}
