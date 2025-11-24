<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Carbon\CarbonImmutable;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateFromConstraintValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof DateFromConstraint) {
            throw new UnexpectedTypeException($constraint, DateFromConstraint::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $dossier = $this->context->getObject();
        if (! $dossier instanceof AbstractDossier) {
            throw new UnexpectedValueException($dossier, AbstractDossier::class);
        }

        $maxDate = $dossier->hasCreatedAt()
            ? $dossier->getCreatedAt()
            : CarbonImmutable::now();

        if ($dossier->getDateFrom() < $maxDate->modify('-10 years')) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
