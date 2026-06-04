<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentProcessor;
use PublicationApi\Api\Publication\Uploads\Attachment\UploadAttachmentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class WooDecisionUploadAttachmentProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadAttachmentProcessor $uploadAttachmentProcessor,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadAttachmentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $this->uploadAttachmentProcessor->process($data, $this->wooDecisionRepository, WooDecisionAttachment::class);
    }
}
