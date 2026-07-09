<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

interface EntityWithNoticeNotPublic
{
    public function getNoticeNotPublic(): ?NoticeNotPublic;

    public function setNoticeNotPublic(?NoticeNotPublic $noticeNotPublic): static;
}
