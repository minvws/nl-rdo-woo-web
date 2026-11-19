<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicMapper;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicService;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;

readonly class NoticeNotPublicSynchronizer
{
    public function __construct(
        private NoticeNotPublicService $noticeNotPublicService,
    ) {
    }

    public function create(
        AbstractDossier&EntityWithNoticeNotPublic $dossier,
        NoticeNotPublicRequestDto $noticeNotPublicRequestDto,
    ): void {
        $dossier->setNoticeNotPublic(NoticeNotPublicMapper::create($dossier, $noticeNotPublicRequestDto));
    }

    public function update(
        AbstractDossier&EntityWithNoticeNotPublic $dossier,
        NoticeNotPublicRequestDto $noticeNotPublicRequestDto,
    ): void {
        $noticeNotPublic = $dossier->getNoticeNotPublic() instanceof NoticeNotPublic
            ? $this->noticeNotPublicService->updateForDossier($dossier, $noticeNotPublicRequestDto)
            : $this->noticeNotPublicService->createForDossier($dossier, $noticeNotPublicRequestDto);

        $dossier->setNoticeNotPublic($noticeNotPublic);
    }

    public function delete(AbstractDossier&EntityWithNoticeNotPublic $dossier): void
    {
        if (! $dossier->getNoticeNotPublic() instanceof NoticeNotPublic) {
            return;
        }

        $this->noticeNotPublicService->deleteFromDossier($dossier);
    }
}
