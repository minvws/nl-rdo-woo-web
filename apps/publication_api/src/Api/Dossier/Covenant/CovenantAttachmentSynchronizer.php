<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;

readonly class CovenantAttachmentSynchronizer
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
    public function create(Covenant $covenant, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($covenant, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(Covenant $covenant, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($covenant, $attachmentRequestDtos);

        return $this->synchronize($covenant, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(Covenant $covenant, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($covenant, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($covenant->getAttachments()->getValues(), $covenant->getStatus());

        return $attachmentEvents;
    }
}
