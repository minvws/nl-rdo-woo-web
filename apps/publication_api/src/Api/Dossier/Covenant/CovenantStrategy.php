<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<Covenant,CovenantRequestDto>
 */
readonly class CovenantStrategy implements DossierStrategy
{
    public function __construct(
        private CovenantPersister $covenantPersister,
        private CovenantRequestMapper $covenantRequestMapper,
        private CovenantAttachmentSynchronizer $covenantAttachmentSynchronizer,
        private CovenantMainDocumentSynchronizer $covenantMainDocumentSynchronizer,
        private NoticeNotPublicSynchronizer $noticeNotPublicSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return Covenant::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, CovenantRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): Covenant {
        Assert::isInstanceOf($dossierRequestDto, CovenantRequestDto::class);

        $covenant = $this->covenantRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        if ($dossierRequestDto->mainDocument !== null) {
            $this->covenantMainDocumentSynchronizer->create($covenant, $dossierRequestDto->mainDocument);
        } else {
            $this->noticeNotPublicSynchronizer->create($covenant, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->covenantAttachmentSynchronizer->create($covenant, $dossierRequestDto->attachments);

        $this->covenantPersister->persist($covenant, null, $attachmentEvents);

        return $covenant;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, Covenant::class);
        Assert::isInstanceOf($dossierRequestDto, CovenantRequestDto::class);

        $this->covenantRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $covenantSnapshot = $this->covenantPersister->snapshot($dossier);

        if ($dossierRequestDto->mainDocument !== null) {
            $this->noticeNotPublicSynchronizer->delete($dossier);
            $this->covenantMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        } else {
            $this->covenantMainDocumentSynchronizer->delete($dossier);
            $this->noticeNotPublicSynchronizer->update($dossier, self::noticeNotPublic($dossierRequestDto));
        }

        $attachmentEvents = $this->covenantAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->covenantPersister->persist($dossier, $covenantSnapshot, $attachmentEvents);
    }

    private static function noticeNotPublic(CovenantRequestDto $covenantRequestDto): NoticeNotPublicRequestDto
    {
        $noticeNotPublic = $covenantRequestDto->noticeNotPublic;
        Assert::notNull($noticeNotPublic);

        return $noticeNotPublic;
    }
}
