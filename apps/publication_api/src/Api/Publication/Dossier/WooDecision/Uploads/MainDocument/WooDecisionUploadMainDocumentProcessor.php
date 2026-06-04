<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class WooDecisionUploadMainDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private UploadMainDocumentProcessor $uploadMainDocumentProcessor,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (! $data instanceof UploadMainDocumentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $this->uploadMainDocumentProcessor->process($data, $this->wooDecisionRepository, WooDecisionMainDocument::class);

        return null;
    }
}
