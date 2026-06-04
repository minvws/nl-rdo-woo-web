<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\WooDecision;

use Symfony\Component\Uid\Uuid;

final readonly class ProcessUploadedDocumentsCommand
{
    public function __construct(
        public Uuid $wooDecisionId,
        public Uuid $uploadEntityId,
    ) {
    }
}
