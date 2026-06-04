<?php

declare(strict_types=1);

namespace Shared\Validator;

use Shared\ValueObject\FileName;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webmozart\Assert\Assert;

use function implode;
use function in_array;
use function strtolower;

class AllowedFileExtensionValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, AllowedFileExtension::class);

        if ($value === null) {
            return;
        }

        if (! $value instanceof FileName) {
            throw new UnexpectedValueException($value, FileName::class);
        }

        $extension = strtolower($value->getExtension());
        $allowedExtensions = $constraint->uploadGroupId->getExtensions();

        if (in_array($extension, $allowedExtensions, strict: true)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ extension }}', $extension)
            ->setParameter('{{ allowed }}', implode(', ', $allowedExtensions))
            ->setCode(AllowedFileExtension::INVALID_EXTENSION_ERROR)
            ->addViolation();
    }
}
