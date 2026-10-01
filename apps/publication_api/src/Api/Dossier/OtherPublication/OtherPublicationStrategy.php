<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<OtherPublication,OtherPublicationRequestDto>
 */
readonly class OtherPublicationStrategy implements DossierStrategy
{
    public function __construct(
        private OtherPublicationPersister $otherPublicationPersister,
        private OtherPublicationRequestMapper $otherPublicationRequestMapper,
        private OtherPublicationAttachmentSynchronizer $otherPublicationAttachmentSynchronizer,
        private OtherPublicationMainDocumentSynchronizer $otherPublicationMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return OtherPublication::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, OtherPublicationRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): OtherPublication {
        Assert::isInstanceOf($dossierRequestDto, OtherPublicationRequestDto::class);

        $otherPublication = $this->otherPublicationRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->otherPublicationMainDocumentSynchronizer->create($otherPublication, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($otherPublication, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->otherPublicationAttachmentSynchronizer->create($otherPublication, $dossierRequestDto->attachments);

        $this->otherPublicationPersister->persist($otherPublication, null, $attachmentEvents);

        return $otherPublication;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, OtherPublication::class);
        Assert::isInstanceOf($dossierRequestDto, OtherPublicationRequestDto::class);

        $this->otherPublicationRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $otherPublicationSnapshot = $this->otherPublicationPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->otherPublicationMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->otherPublicationMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->otherPublicationAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->otherPublicationPersister->persist($dossier, $otherPublicationSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(OtherPublicationRequestDto $otherPublicationRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $otherPublicationRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
