<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Uploads\Attachment;

use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Http\Message\StreamInterface;
use PublicationApi\Api\Publication\AttachmentUploadProcessor;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class UploadAttachmentHandler
{
    public function __construct(
        private AttachmentUploadProcessor $attachmentUploadProcessor,
    ) {
    }

    public function handle(AbstractDossier $dossier, AbstractAttachment $attachment, StreamInterface $content): void
    {
        if ($content->getSize() === 0) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $this->attachmentUploadProcessor->process($dossier, $attachment, $content);
    }
}
