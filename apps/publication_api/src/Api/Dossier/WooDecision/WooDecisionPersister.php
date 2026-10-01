<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentSynchronizer;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\WooDecisionInquirySynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;

readonly class WooDecisionPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
        private WooDecisionRepository $wooDecisionRepository,
        private WooDecisionDocumentSynchronizer $wooDecisionDocumentSynchronizer,
        private WooDecisionInquirySynchronizer $wooDecisionInquirySynchronizer,
    ) {
    }

    public function snapshot(WooDecision $wooDecision): WooDecisionSnapshot
    {
        return WooDecisionSnapshot::of(
            $wooDecision,
            $this->wooDecisionInquirySynchronizer->snapshot($wooDecision),
        );
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        WooDecision $wooDecision,
        WooDecisionRequestDto $wooDecisionRequestDto,
        ?WooDecisionSnapshot $wooDecisionSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($wooDecision);

        $this->wooDecisionRepository->save($wooDecision, true);

        $requestDocuments = $wooDecisionRequestDto->documents;

        if ($wooDecisionSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($wooDecision);
            $this->wooDecisionInquirySynchronizer->apply($wooDecision, $requestDocuments);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($wooDecision, $wooDecisionSnapshot->mainDocument, $attachmentEvents);
            $this->wooDecisionInquirySynchronizer->apply($wooDecision, $requestDocuments, $wooDecisionSnapshot->documentInquiryNumbers);
        }

        $this->wooDecisionDocumentSynchronizer->synchronizeRefersTo($requestDocuments);

        $this->dossierSupportService->synchronizeArtifacts($wooDecision);
    }
}
