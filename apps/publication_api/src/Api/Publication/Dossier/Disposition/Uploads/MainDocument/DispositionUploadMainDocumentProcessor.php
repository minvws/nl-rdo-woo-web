<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class DispositionUploadMainDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DispositionRepository $dispositionRepository,
        private UploadMainDocumentProcessor $uploadMainDocumentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (! $data instanceof UploadMainDocumentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $this->uploadMainDocumentProcessor->process($data, $this->dispositionRepository, DispositionMainDocument::class);

        return null;
    }
}
