<?php

declare(strict_types=1);

namespace Shared\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class MainDocumentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationFailedException
     */
    public function validate(AbstractMainDocument $mainDocument): void
    {
        $errors = $this->validator->validate($mainDocument);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($mainDocument, $errors);
        }
    }

    public function refreshMainDocument(AbstractMainDocument $mainDocument): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        if ($this->entityManager->contains($mainDocument) && ! $unitOfWork->isScheduledForInsert($mainDocument)) {
            $this->entityManager->refresh($mainDocument);
        }
    }
}
