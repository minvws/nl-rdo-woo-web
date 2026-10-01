<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<Advice,AdviceRequestDto>
 */
readonly class AdviceStrategy implements DossierStrategy
{
    public function __construct(
        private AdvicePersister $advicePersister,
        private AdviceRequestMapper $adviceRequestMapper,
        private AdviceAttachmentSynchronizer $adviceAttachmentSynchronizer,
        private AdviceMainDocumentSynchronizer $adviceMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return Advice::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, AdviceRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Advice {
        Assert::isInstanceOf($dossierRequestDto, AdviceRequestDto::class);

        $advice = $this->adviceRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->adviceMainDocumentSynchronizer->create($advice, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($advice, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->adviceAttachmentSynchronizer->create($advice, $dossierRequestDto->attachments);

        $this->advicePersister->persist($advice, null, $attachmentEvents);

        return $advice;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, Advice::class);
        Assert::isInstanceOf($dossierRequestDto, AdviceRequestDto::class);

        $this->adviceRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $adviceSnapshot = $this->advicePersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->adviceMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->adviceMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->adviceAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->advicePersister->persist($dossier, $adviceSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(AdviceRequestDto $adviceRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $adviceRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
