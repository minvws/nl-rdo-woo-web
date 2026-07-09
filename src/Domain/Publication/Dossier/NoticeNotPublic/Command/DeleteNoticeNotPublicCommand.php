<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Command;

use Symfony\Component\Uid\Uuid;

readonly class DeleteNoticeNotPublicCommand
{
    public function __construct(
        public Uuid $dossierId,
    ) {
    }
}
