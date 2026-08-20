<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class DraftDecisionUploadMainDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DraftDecisionRepository $draftDecisionRepository,
        private UploadMainDocumentProcessor $uploadMainDocumentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadMainDocumentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $this->uploadMainDocumentProcessor->process($data, $this->draftDecisionRepository, DraftDecisionMainDocument::class);
    }
}
