<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;

readonly class OtherPublicationAttachmentSynchronizer
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
    public function create(OtherPublication $otherPublication, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($otherPublication, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(OtherPublication $otherPublication, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($otherPublication, $attachmentRequestDtos);

        return $this->synchronize($otherPublication, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(OtherPublication $otherPublication, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($otherPublication, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($otherPublication->getAttachments()->getValues(), $otherPublication->getStatus());

        return $attachmentEvents;
    }
}
