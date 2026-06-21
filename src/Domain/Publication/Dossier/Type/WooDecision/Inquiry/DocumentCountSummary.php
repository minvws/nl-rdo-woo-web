<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

final readonly class DocumentCountSummary
{
    public function __construct(
        public int $alreadyPublic,
        public int $notPublic,
        public int $partialPublic,
        public int $partialPublicSuspended,
        public int $partialPublicWithdrawn,
        public int $public,
        public int $publicSuspended,
        public int $publicWithdrawn,
    ) {
    }
}
