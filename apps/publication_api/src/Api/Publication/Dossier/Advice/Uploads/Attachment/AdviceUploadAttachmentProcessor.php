<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Advice\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class AdviceUploadAttachmentProcessor implements ProcessorInterface
{
    public function __construct(
        private AdviceRepository $adviceRepository,
        private UploadAttachmentProcessor $uploadAttachmentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadAttachmentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $this->uploadAttachmentProcessor->process($data, $this->adviceRepository, AdviceAttachment::class);
    }
}
