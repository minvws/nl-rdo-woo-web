<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoIncompleteDocumentsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof NoIncompleteDocuments) {
            throw new UnexpectedTypeException($constraint, NoIncompleteDocuments::class);
        }

        if (! $value instanceof AbstractDossier) {
            throw new UnexpectedValueException($value, AbstractDossier::class);
        }

        if ($this->documentRepository->hasIncompleteDocumentsForDossier($value->getId())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
