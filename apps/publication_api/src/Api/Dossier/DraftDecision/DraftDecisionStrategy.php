<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<DraftDecision,DraftDecisionRequestDto>
 */
readonly class DraftDecisionStrategy implements DossierStrategy
{
    public function __construct(
        private DossierPersister $dossierPersister,
        private DraftDecisionRequestMapper $draftDecisionRequestMapper,
        private DraftDecisionAttachmentSynchronizer $draftDecisionAttachmentSynchronizer,
        private DraftDecisionMainDocumentRequestMapper $draftDecisionMainDocumentRequestMapper,
        private DossierMainDocumentSynchronizer $dossierMainDocumentSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return DraftDecision::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, DraftDecisionRequestDto::class);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): DraftDecision {
        Assert::isInstanceOf($dossierRequestDto, DraftDecisionRequestDto::class);

        $draftDecision = $this->draftDecisionRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        $this->dossierMainDocumentSynchronizer->create(
            $draftDecision,
            self::mainDocument($dossierRequestDto),
            $this->draftDecisionMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->draftDecisionAttachmentSynchronizer->create($draftDecision, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($draftDecision, null, $attachmentEvents);

        return $draftDecision;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, DraftDecision::class);
        Assert::isInstanceOf($dossierRequestDto, DraftDecisionRequestDto::class);

        $this->draftDecisionRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $mainDocumentSnapshot = $this->dossierPersister->snapshot($dossier);

        $this->dossierMainDocumentSynchronizer->update(
            $dossier,
            self::mainDocument($dossierRequestDto),
            $this->draftDecisionMainDocumentRequestMapper,
        );

        $attachmentEvents = $this->draftDecisionAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);

        $this->dossierPersister->persist($dossier, $mainDocumentSnapshot, $attachmentEvents);
    }

    private static function mainDocument(DraftDecisionRequestDto $draftDecisionRequestDto): DraftDecisionMainDocumentRequestDto
    {
        $mainDocument = $draftDecisionRequestDto->mainDocument;
        Assert::notNull($mainDocument);

        return $mainDocument;
    }
}
