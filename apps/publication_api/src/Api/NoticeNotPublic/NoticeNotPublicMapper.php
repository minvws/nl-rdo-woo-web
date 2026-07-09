<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Symfony\Component\Uid\Uuid;

class NoticeNotPublicMapper
{
    public static function create(
        AbstractDossier $dossier,
        NoticeNotPublicRequestDto $dto,
    ): NoticeNotPublic {
        return new NoticeNotPublic(
            id: Uuid::v6(),
            dossier: $dossier,
            documentName: $dto->documentName,
            formalDate: $dto->formalDate,
            grounds: $dto->grounds,
            explanation: $dto->explanation,
        );
    }
}
