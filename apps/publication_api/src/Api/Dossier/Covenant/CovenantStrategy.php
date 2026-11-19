<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
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
        private DossierPersister $dossierPersister,
        private CovenantRequestMapper $covenantRequestMapper,
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
        private CovenantMainDocumentRequestMapper $covenantMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
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

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $covenant,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->covenantMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->create($covenant, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($covenant, null, $attachmentEvents);

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

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->covenantMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }
}
