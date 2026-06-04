<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use DateTimeImmutable;
use DateTimeInterface;
use Shared\ValueObject\PlainDate;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Webmozart\Assert\Assert;

abstract class PlainDateValidator extends ConstraintValidator
{
    public function __construct(
        private ?PropertyAccessorInterface $propertyAccessor = null,
    ) {
    }

    abstract protected function validatePlainDate(PlainDate $value, PlainDate $comparedValue, string $message): void;

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof PlainDateConstraint) {
            throw new UnexpectedTypeException($constraint, PlainDateConstraint::class);
        }

        if ($value === null) {
            return;
        }

        if (! $value instanceof PlainDate) {
            throw new UnexpectedValueException($constraint, PlainDate::class);
        }

        if ($constraint->propertyPath !== null) {
            $this->validatePropertyPath($value, $constraint);

            return;
        }

        Assert::string($constraint->date);

        $comparedValue = PlainDate::create(new DateTimeImmutable($constraint->date)->format(PlainDate::DEFAULT_STRING_FORMAT));
        $this->validatePlainDate($value, $comparedValue, $constraint->message);
    }

    protected function getPropertyAccessor(): PropertyAccessorInterface
    {
        return $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }

    private function validatePropertyPath(PlainDate $value, PlainDateConstraint $constraint): void
    {
        $object = $this->context->getObject();
        if ($object === null) {
            return;
        }

        Assert::string($constraint->propertyPath);

        try {
            $comparedValue = $this->getPropertyAccessor()->getValue($object, $constraint->propertyPath);
        } catch (NoSuchPropertyException) {
            throw new ConstraintDefinitionException('Invalid property path provided');
        } catch (UninitializedPropertyException) {
            $comparedValue = null;
        }

        if ($comparedValue instanceof DateTimeInterface) {
            $comparedValue = PlainDate::create($comparedValue->format('Y-m-d'));
        }

        if (! $comparedValue instanceof PlainDate) {
            throw new UnexpectedValueException($comparedValue, PlainDate::class);
        }

        if ($constraint->date !== null) {
            $date = DateTimeImmutable::createFromFormat(PlainDate::DEFAULT_STRING_FORMAT, $comparedValue->toString());
            Assert::isInstanceOf($date, DateTimeImmutable::class);

            $date = $date
                ->modify($constraint->date)
                ->format(PlainDate::DEFAULT_STRING_FORMAT);

            $comparedValue = PlainDate::createFromFormat(PlainDate::DEFAULT_STRING_FORMAT, $date);
        }

        $this->validatePlainDate($value, $comparedValue, $constraint->message);
    }
}
