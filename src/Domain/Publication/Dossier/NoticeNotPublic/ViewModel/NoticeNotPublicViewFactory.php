<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel;

use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class NoticeNotPublicViewFactory
{
    public function __construct(
        private GroundViewFactory $groundViewFactory,
        private TranslatorInterface $translator,
        private DossierUrlGenerator $dossierUrlGenerator,
    ) {
    }

    public function make(
        AbstractDossier&EntityWithNoticeNotPublic $dossier,
    ): NoticeNotPublic {
        $noticeNotPublic = $dossier->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            throw new NoticeNotPublicNotFoundException();
        }

        return new NoticeNotPublic(
            id: $noticeNotPublic->getId()->toRfc4122(),
            documentName: $noticeNotPublic->getDocumentName(),
            formalDate: $noticeNotPublic->getFormalDate()->format('Y-m-d'),
            grounds: $this->groundViewFactory->makeCollection(Citation::sortWooCitations($noticeNotPublic->getGrounds())),
            explanation: $noticeNotPublic->getExplanation(),
            detailsUrl: $this->dossierUrlGenerator->noticeNotPublicDetailPath($dossier),
            title: $noticeNotPublic->getDocumentName() ?: $this->translator->trans('global.dossiers.notice_not_public'),
        );
    }
}
