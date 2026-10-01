<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<Disposition,DispositionRequestDto>
 */
readonly class DispositionStrategy implements DossierStrategy
{
    public function __construct(
        private DispositionPersister $dispositionPersister,
        private DispositionRequestMapper $dispositionRequestMapper,
        private DispositionAttachmentSynchronizer $dispositionAttachmentSynchronizer,
        private DispositionMainDocumentSynchronizer $dispositionMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return Disposition::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, DispositionRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Disposition {
        Assert::isInstanceOf($dossierRequestDto, DispositionRequestDto::class);

        $disposition = $this->dispositionRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->dispositionMainDocumentSynchronizer->create($disposition, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($disposition, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->dispositionAttachmentSynchronizer->create($disposition, $dossierRequestDto->attachments);

        $this->dispositionPersister->persist($disposition, null, $attachmentEvents);

        return $disposition;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, Disposition::class);
        Assert::isInstanceOf($dossierRequestDto, DispositionRequestDto::class);

        $this->dispositionRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $dispositionSnapshot = $this->dispositionPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->dispositionMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->dispositionMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->dispositionAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dispositionPersister->persist($dossier, $dispositionSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(DispositionRequestDto $dispositionRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $dispositionRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
