<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class OtherPublicationUploadAttachmentProcessor implements ProcessorInterface
{
    public function __construct(
        private OtherPublicationRepository $otherPublicationRepository,
        private UploadAttachmentProcessor $uploadAttachmentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadAttachmentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $this->uploadAttachmentProcessor->process($data, $this->otherPublicationRepository, OtherPublicationAttachment::class);
    }
}
