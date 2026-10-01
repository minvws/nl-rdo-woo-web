<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;

readonly class RequestForAdviceAttachmentSynchronizer
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
    public function create(RequestForAdvice $requestForAdvice, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);

        return $this->synchronize($requestForAdvice, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function update(RequestForAdvice $requestForAdvice, array $attachmentRequestDtos): array
    {
        $this->dossierAttachmentValidator->assertUniqueExternalIds($attachmentRequestDtos);
        $this->dossierAttachmentValidator->assertNoAttachmentRemovalInNonConcept($requestForAdvice, $attachmentRequestDtos);

        return $this->synchronize($requestForAdvice, $attachmentRequestDtos);
    }

    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    private function synchronize(RequestForAdvice $requestForAdvice, array $attachmentRequestDtos): array
    {
        $attachmentEvents = $this->attachmentSynchronizer->sync($requestForAdvice, $attachmentRequestDtos);
        $this->dossierAttachmentValidator->validate($requestForAdvice->getAttachments()->getValues(), $requestForAdvice->getStatus());

        return $attachmentEvents;
    }
}
