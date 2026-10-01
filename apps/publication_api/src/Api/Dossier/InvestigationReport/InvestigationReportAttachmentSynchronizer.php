<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;

readonly class InvestigationReportAttachmentSynchronizer
{
    public function __construct(
        private AttachmentSynchronizer $attachmentSynchronizer,
        private DossierAttachmentValidator $dossierAttachmentValidator,
    ) {
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function create(InvestigationReport $investigationReport, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($investigationReport, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(InvestigationReport $investigationReport, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($investigationReport, $attachmentRequestDtos);

        return $this->synchronize($investigationReport, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(InvestigationReport $investigationReport, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($investigationReport, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($investigationReport->getAttachments()->getValues(), $investigationReport->getStatus());

        return $attachmentEvents;
    }
}
