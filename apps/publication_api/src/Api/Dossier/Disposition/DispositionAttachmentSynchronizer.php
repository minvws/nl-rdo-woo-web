<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;

readonly class DispositionAttachmentSynchronizer
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
    public function create(Disposition $disposition, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($disposition, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(Disposition $disposition, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($disposition, $attachmentRequestDtos);

        return $this->synchronize($disposition, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(Disposition $disposition, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($disposition, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($disposition->getAttachments()->getValues(), $disposition->getStatus());

        return $attachmentEvents;
    }
}
