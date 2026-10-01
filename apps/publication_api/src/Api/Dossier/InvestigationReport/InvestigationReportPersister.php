<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;

readonly class InvestigationReportPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(InvestigationReport $investigationReport): InvestigationReportSnapshot
    {
        return InvestigationReportSnapshot::of($investigationReport);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        InvestigationReport $investigationReport,
        ?InvestigationReportSnapshot $investigationReportSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($investigationReport);
        $this->dossierSupportService->autoPublish($investigationReport);
        $this->dossierSupportService->validateCompletionAndPersist($investigationReport);

        if ($investigationReportSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($investigationReport);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents(
                $investigationReport,
                $investigationReportSnapshot->mainDocument,
                $attachmentEvents,
            );
        }

        $this->dossierSupportService->synchronizeArtifacts($investigationReport);
    }
}
