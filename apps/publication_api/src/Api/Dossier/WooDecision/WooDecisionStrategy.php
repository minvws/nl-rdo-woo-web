<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierRequestDtoInterface;
use PublicationApi\Api\Dossier\DossierStrategy;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentSynchronizer;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentWriteGuard;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

/**
 * @implements DossierStrategy<WooDecision,WooDecisionRequestDto>
 */
readonly class WooDecisionStrategy implements DossierStrategy
{
    public function __construct(
        private WooDecisionPersister $wooDecisionPersister,
        private WooDecisionRequestMapper $wooDecisionRequestMapper,
        private WooDecisionAttachmentSynchronizer $wooDecisionAttachmentSynchronizer,
        private WooDecisionDocumentSynchronizer $wooDecisionDocumentSynchronizer,
        private WooDecisionDocumentWriteGuard $wooDecisionDocumentWriteGuard,
        private WooDecisionMainDocumentSynchronizer $wooDecisionMainDocumentSynchronizer,
    ) {
    }

    public function dossierType(): string
    {
        return WooDecision::class;
    }

    public function validateRequest(DossierRequestDtoInterface $dossierRequestDto): void
    {
        Assert::isInstanceOf($dossierRequestDto, WooDecisionRequestDto::class);

        $this->wooDecisionDocumentSynchronizer->validateRequest($dossierRequestDto->documents);
    }

    public function create(
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $dossierExternalId,
        string $documentPrefix,
    ): WooDecision {
        Assert::isInstanceOf($dossierRequestDto, WooDecisionRequestDto::class);

        $this->wooDecisionDocumentWriteGuard->assertCanWrite(
            null,
            $this->extractDocumentNumbers($dossierRequestDto->documents),
        );

        $wooDecision = $this->wooDecisionRequestMapper->create(
            $dossierRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        $this->wooDecisionMainDocumentSynchronizer->create($wooDecision, $dossierRequestDto->mainDocument);
        $attachmentEvents = $this->wooDecisionAttachmentSynchronizer->create($wooDecision, $dossierRequestDto->attachments);
        $this->wooDecisionDocumentSynchronizer->create($wooDecision, $dossierRequestDto->documents);

        $this->wooDecisionPersister->persist($wooDecision, $dossierRequestDto, null, $attachmentEvents);

        return $wooDecision;
    }

    public function update(
        AbstractDossier $dossier,
        DossierRequestDtoInterface $dossierRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): void {
        Assert::isInstanceOf($dossier, WooDecision::class);
        Assert::isInstanceOf($dossierRequestDto, WooDecisionRequestDto::class);

        $this->wooDecisionDocumentWriteGuard->assertCanWrite(
            $dossier,
            $this->extractDocumentNumbers($dossierRequestDto->documents),
        );

        $this->wooDecisionRequestMapper->update($dossier, $dossierRequestDto, $organisation, $department, $subject);

        $wooDecisionSnapshot = $this->wooDecisionPersister->snapshot($dossier);

        $this->wooDecisionMainDocumentSynchronizer->update($dossier, $dossierRequestDto->mainDocument);
        $attachmentEvents = $this->wooDecisionAttachmentSynchronizer->update($dossier, $dossierRequestDto->attachments);
        $this->wooDecisionDocumentSynchronizer->update($dossier, $dossierRequestDto->documents);

        $this->wooDecisionPersister->persist($dossier, $dossierRequestDto, $wooDecisionSnapshot, $attachmentEvents);
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $documentRequests
     *
     * @return list<DocumentNumber>
     */
    private function extractDocumentNumbers(array $documentRequests): array
    {
        $documentNumbers = [];

        foreach ($documentRequests as $documentRequest) {
            $documentNumbers[] = DocumentNumber::fromPublicationContextAndDocumentId(
                $documentRequest->publicationContext,
                $documentRequest->documentId,
            );
        }

        return $documentNumbers;
    }
}
