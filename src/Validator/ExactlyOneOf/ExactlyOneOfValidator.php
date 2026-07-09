<?php

declare(strict_types=1);

namespace Shared\Validator\ExactlyOneOf;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_object;

class ExactlyOneOfValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ExactlyOneOf) {
            throw new UnexpectedTypeException($constraint, ExactlyOneOf::class);
        }

        if (! is_object($value)) {
            throw new UnexpectedValueException($value, 'object');
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $nonNullCount = 0;

        foreach ($constraint->properties as $property) {
            if ($accessor->getValue($value, $property) !== null) {
                $nonNullCount++;
            }
        }

        if ($nonNullCount === 1) {
            return;
        }

        if ($nonNullCount === 0) {
            $message = $constraint->noneMessage;
        } else {
            $message = $constraint->multipleMessage;
        }

        if ($constraint->errorPaths === []) {
            $this->context->buildViolation($message)->addViolation();

            return;
        }

        foreach ($constraint->errorPaths as $errorPath) {
            $this->context->buildViolation($message)
                ->atPath($errorPath)
                ->addViolation();
        }
    }
}
