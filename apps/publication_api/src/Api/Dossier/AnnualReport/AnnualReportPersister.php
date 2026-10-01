<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;

readonly class AnnualReportPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(AnnualReport $annualReport): AnnualReportSnapshot
    {
        return AnnualReportSnapshot::of($annualReport);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        AnnualReport $annualReport,
        ?AnnualReportSnapshot $annualReportSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($annualReport);
        $this->dossierSupportService->autoPublish($annualReport);
        $this->dossierSupportService->validateCompletionAndPersist($annualReport);

        if ($annualReportSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($annualReport);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($annualReport, $annualReportSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($annualReport);
    }
}
