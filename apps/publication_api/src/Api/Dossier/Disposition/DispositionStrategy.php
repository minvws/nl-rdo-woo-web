<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
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
        private DossierPersister $dossierPersister,
        private DispositionRequestMapper $dispositionRequestMapper,
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
        private DispositionMainDocumentRequestMapper $dispositionMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
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

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $disposition,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->dispositionMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->create($disposition, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($disposition, null, $attachmentEvents);

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

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->dispositionMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }
}
