<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Publication\Dossier\AbstractDossier;

use function array_key_exists;

class AttachmentSynchronizer
{
    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     *
     * @return list<AbstractAttachmentEvent>
     */
    public function sync(AbstractDossier&EntityWithAttachments $dossier, array $attachmentRequestDtos): array
    {
        $incomingByExternalId = [];
        foreach ($attachmentRequestDtos as $attachmentRequestDto) {
            $incomingByExternalId[$attachmentRequestDto->externalId->toString()] = $attachmentRequestDto;
        }

        $events = [];

        foreach ($dossier->getAttachments()->toArray() as $existingAttachment) {
            $externalId = $existingAttachment->getExternalId()?->toString();

            if ($externalId !== null && array_key_exists($externalId, $incomingByExternalId)) {
                $snapshot = MetadataSnapshot::of($existingAttachment);
                AttachmentMapper::updateFromRequestDto($existingAttachment, $incomingByExternalId[$externalId]);

                if (! $snapshot->equalTo(MetadataSnapshot::of($existingAttachment))) {
                    $events[] = AttachmentUpdatedEvent::forAttachmentWithMetadataUpdated($existingAttachment);
                }

                unset($incomingByExternalId[$externalId]);
            } else {
                $events[] = AttachmentDeletedEvent::forAttachment($existingAttachment);
                $dossier->removeAttachment($existingAttachment);
            }
        }

        foreach ($incomingByExternalId as $incomingAttachmentRequestDto) {
            $dossier->addAttachment(AbstractAttachmentFactory::createFromRequestDto($dossier, $incomingAttachmentRequestDto));
        }

        return $events;
    }
}
