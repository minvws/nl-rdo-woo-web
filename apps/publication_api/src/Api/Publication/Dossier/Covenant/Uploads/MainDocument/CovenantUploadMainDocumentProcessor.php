<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentProcessor;
use PublicationApi\Api\Publication\Uploads\MainDocument\UploadMainDocumentRequestInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Symfony\Component\Validator\ConstraintViolationList;

final readonly class CovenantUploadMainDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private CovenantRepository $covenantRepository,
        private UploadMainDocumentProcessor $uploadMainDocumentProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadMainDocumentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $this->uploadMainDocumentProcessor->process($data, $this->covenantRepository, CovenantMainDocument::class);
    }
}
