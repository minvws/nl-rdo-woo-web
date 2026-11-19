<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use PublicationApi\Api\MainDocument\MainDocumentRequestDtoInterface;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Webmozart\Assert\Assert;

readonly class DossierMainDocumentOrNoticeSynchronizer
{
    public function __construct(
        private DossierMainDocumentSynchronizer $dossierMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    /**
     * @template TDossier of AbstractDossier&EntityWithMainDocument&EntityWithNoticeNotPublic
     * @template TMainDocumentRequestDto of MainDocumentRequestDtoInterface
     *
     * @param TDossier $dossier
     * @param ?TMainDocumentRequestDto $mainDocumentRequestDto
     * @param MainDocumentRequestMapperInterface<TDossier,TMainDocumentRequestDto> $mainDocumentRequestMapper
     */
    public function create(
        AbstractDossier&EntityWithMainDocument&EntityWithNoticeNotPublic $dossier,
        ?MainDocumentRequestDtoInterface $mainDocumentRequestDto,
        ?NoticeNotPublicRequestDto $noticeNotPublicRequestDto,
        MainDocumentRequestMapperInterface $mainDocumentRequestMapper,
    ): void {
        if ($mainDocumentRequestDto instanceof MainDocumentRequestDtoInterface) {
            $this->dossierMainDocumentSynchronizer->create($dossier, $mainDocumentRequestDto, $mainDocumentRequestMapper);

            return;
        }

        Assert::notNull($noticeNotPublicRequestDto);
        $this->noticeNotPublicSynchronizer->create($dossier, $noticeNotPublicRequestDto);
    }

    /**
     * @template TDossier of AbstractDossier&EntityWithMainDocument&EntityWithNoticeNotPublic
     * @template TMainDocumentRequestDto of MainDocumentRequestDtoInterface
     *
     * @param TDossier $dossier
     * @param ?TMainDocumentRequestDto $mainDocumentRequestDto
     * @param MainDocumentRequestMapperInterface<TDossier,TMainDocumentRequestDto> $mainDocumentRequestMapper
     */
    public function update(
        AbstractDossier&EntityWithMainDocument&EntityWithNoticeNotPublic $dossier,
        ?MainDocumentRequestDtoInterface $mainDocumentRequestDto,
        ?NoticeNotPublicRequestDto $noticeNotPublicRequestDto,
        MainDocumentRequestMapperInterface $mainDocumentRequestMapper,
    ): void {
        if ($mainDocumentRequestDto instanceof MainDocumentRequestDtoInterface) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->dossierMainDocumentSynchronizer->update($dossier, $mainDocumentRequestDto, $mainDocumentRequestMapper);

            return;
        }

        $this->dossierMainDocumentSynchronizer->delete($dossier);

        Assert::notNull($noticeNotPublicRequestDto);
        $this->noticeNotPublicSynchronizer->update($dossier, $noticeNotPublicRequestDto);
    }
}
