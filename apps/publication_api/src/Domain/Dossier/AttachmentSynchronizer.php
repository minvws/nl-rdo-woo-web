<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;

use function array_key_exists;

class AttachmentSynchronizer
{
    /**
     * @param list<AttachmentRequestDto> $attachmentRequestDtos
     */
    public function sync(AbstractDossier&EntityWithAttachments $dossier, array $attachmentRequestDtos): void
    {
        $incomingByExternalId = [];
        foreach ($attachmentRequestDtos as $attachmentRequestDto) {
            $incomingByExternalId[$attachmentRequestDto->externalId->toString()] = $attachmentRequestDto;
        }

        foreach ($dossier->getAttachments()->toArray() as $existingAttachment) {
            $externalId = $existingAttachment->getExternalId()?->toString();

            if ($externalId !== null && array_key_exists($externalId, $incomingByExternalId)) {
                AttachmentMapper::updateFromRequestDto($existingAttachment, $incomingByExternalId[$externalId]);
                unset($incomingByExternalId[$externalId]);
            } else {
                $dossier->removeAttachment($existingAttachment);
            }
        }

        foreach ($incomingByExternalId as $incomingAttachmentRequestDto) {
            $dossier->addAttachment(AbstractAttachmentFactory::createFromRequestDto($dossier, $incomingAttachmentRequestDto));
        }
    }
}
