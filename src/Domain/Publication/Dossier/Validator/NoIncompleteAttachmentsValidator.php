<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NoIncompleteAttachmentsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AttachmentRepository $attachmentRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof NoIncompleteAttachments) {
            throw new UnexpectedTypeException($constraint, NoIncompleteAttachments::class);
        }

        if (! $value instanceof AbstractDossier) {
            throw new UnexpectedValueException($value, AbstractDossier::class);
        }

        if ($this->attachmentRepository->hasIncompleteAttachmentsForDossier($value->getId())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
