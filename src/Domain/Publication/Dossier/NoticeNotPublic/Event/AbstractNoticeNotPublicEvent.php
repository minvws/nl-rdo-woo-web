<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Event;

use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractNoticeNotPublicEvent
{
    final public function __construct(
        public Uuid $noticeId,
        public Uuid $dossierId,
    ) {
    }

    public static function forNotice(NoticeNotPublic $notice): static
    {
        return new static(
            $notice->getId(),
            $notice->getDossier()->getId(),
        );
    }
}
