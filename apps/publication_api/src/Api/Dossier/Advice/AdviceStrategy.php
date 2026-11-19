<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
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
        private DossierPersister $dossierPersister,
        private AdviceRequestMapper $adviceRequestMapper,
        private AdviceAttachmentSynchronizer $adviceAttachmentSynchronizer,
        private AdviceMainDocumentRequestMapper $adviceMainDocumentRequestMapper,
        private DossierMainDocumentOrNoticeSynchronizer $dossierMainDocumentOrNoticeSynchronizer,
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

        $this->dossierMainDocumentOrNoticeSynchronizer->create(
            $advice,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->adviceMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->adviceAttachmentSynchronizer->create($advice, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($advice, null, $attachmentEvents);

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

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentOrNoticeSynchronizer->update(
            $dossier,
            $dossierRequestDto->mainDocument,
            $dossierRequestDto->noticeNotPublic,
            $this->adviceMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->adviceAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }
}
