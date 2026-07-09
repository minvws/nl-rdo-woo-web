<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\ViewModel;

use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function sprintf;

readonly class NoticeNotPublicViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function make(
        AbstractDossier&EntityWithNoticeNotPublic $dossier,
    ): NoticeNotPublic {
        $noticeNotPublic = $dossier->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            throw new NoticeNotPublicNotFoundException();
        }

        $detailsUrl = $this->urlGenerator->generate(
            sprintf('app_%s_notice_not_public_detail', $dossier->getType()->getValueForRouteName()),
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
            ],
        );

        return new NoticeNotPublic(
            id: $noticeNotPublic->getId()->toRfc4122(),
            documentName: $noticeNotPublic->getDocumentName(),
            formalDate: $noticeNotPublic->getFormalDate()->format('Y-m-d'),
            grounds: Citation::sortWooCitations($noticeNotPublic->getGrounds()),
            explanation: $noticeNotPublic->getExplanation(),
            detailsUrl: $detailsUrl,
            title: $noticeNotPublic->getDocumentName() ?: $this->translator->trans('global.dossiers.notice_not_public'),
        );
    }
}
