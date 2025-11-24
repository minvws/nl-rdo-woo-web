<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class MainDocumentService
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationFailedException
     */
    public function validate(AbstractMainDocument $mainDocument): void
    {
        $errors = $this->validator->validate($mainDocument, groups: \array_column(DossierValidationGroup::cases(), 'value'));

        if ($errors->count() > 0) {
            throw new ValidationFailedException($mainDocument, $errors);
        }
    }
}
