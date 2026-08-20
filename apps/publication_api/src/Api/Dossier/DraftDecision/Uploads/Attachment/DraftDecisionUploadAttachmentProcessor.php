<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class DraftDecisionUploadAttachmentProcessor implements ProcessorInterface
{
    public function __construct(
        private DraftDecisionRepository $draftDecisionRepository,
        private UploadAttachmentProcessor $uploadAttachmentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadAttachmentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $this->uploadAttachmentProcessor->process($data, $this->draftDecisionRepository, DraftDecisionAttachment::class);
    }
}
