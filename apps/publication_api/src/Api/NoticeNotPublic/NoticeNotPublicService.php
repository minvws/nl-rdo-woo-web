<?php

declare(strict_types=1);

namespace PublicationApi\Api\NoticeNotPublic;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\CreateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\DeleteNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\UpdateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

class NoticeNotPublicService
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function createForDossier(
        AbstractDossier $dossier,
        NoticeNotPublicRequestDto $dto,
    ): NoticeNotPublic {
        $result = $this->handle(new CreateNoticeNotPublicCommand(
            dossierId: $dossier->getId(),
            documentName: $dto->documentName,
            formalDate: $dto->formalDate,
            grounds: $dto->grounds,
            explanation: $dto->explanation,
        ));
        Assert::isInstanceOf($result, NoticeNotPublic::class);

        return $result;
    }

    public function updateForDossier(
        AbstractDossier $dossier,
        NoticeNotPublicRequestDto $dto,
    ): NoticeNotPublic {
        $result = $this->handle(new UpdateNoticeNotPublicCommand(
            dossierId: $dossier->getId(),
            documentName: $dto->documentName,
            formalDate: $dto->formalDate,
            grounds: $dto->grounds,
            explanation: $dto->explanation,
        ));
        Assert::isInstanceOf($result, NoticeNotPublic::class);

        return $result;
    }

    public function deleteFromDossier(AbstractDossier $dossier): void
    {
        $this->handle(new DeleteNoticeNotPublicCommand($dossier->getId()));
    }
}
