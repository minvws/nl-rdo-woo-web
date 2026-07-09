<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

use Doctrine\ORM\Mapping as ORM;

trait HasNoticeNotPublicTrait
{
    #[ORM\OneToOne(targetEntity: NoticeNotPublic::class, mappedBy: 'dossier', cascade: ['persist', 'remove'])]
    private ?NoticeNotPublic $noticeNotPublic = null;

    public function getNoticeNotPublic(): ?NoticeNotPublic
    {
        return $this->noticeNotPublic;
    }

    public function setNoticeNotPublic(?NoticeNotPublic $noticeNotPublic): static
    {
        $this->noticeNotPublic = $noticeNotPublic;

        return $this;
    }
}
