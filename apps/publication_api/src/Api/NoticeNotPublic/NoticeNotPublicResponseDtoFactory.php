<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;

readonly class NoticeNotPublicResponseDtoFactory
{
    public function fromEntity(NoticeNotPublic $noticeNotPublic): NoticeNotPublicResponseDto
    {
        return new NoticeNotPublicResponseDto(
            $noticeNotPublic->getId(),
            $noticeNotPublic->getFormalDate(),
            $noticeNotPublic->getDocumentName(),
            $noticeNotPublic->getGrounds(),
            $noticeNotPublic->getExplanation(),
        );
    }
}
