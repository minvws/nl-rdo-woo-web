<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
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
        private DossierPersister $dossierPersister,
        private OtherPublicationRequestMapper $otherPublicationRequestMapper,
        private DossierAttachmentSynchronizer $dossierAttachmentSynchronizer,
        private OtherPublicationMainDocumentRequestMapper $otherPublicationMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
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

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $otherPublication,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->otherPublicationMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->create($otherPublication, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($otherPublication, null, $attachmentEvents);

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

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->otherPublicationMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->dossierAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }
}
