<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Shared\Validator\PlainDate\PlainDateAfter;
use Shared\Validator\PlainDate\PlainDateAfterValidator;
use Shared\Validator\PlainDate\PlainDateBefore;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
class PlainDateAfterValidatorTest extends ConstraintValidatorTestCase
{
    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateAfter('2000-01-01'));
    }

    public function testThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateBefore('2000-01-01'));
    }

    public function testNoDateOrPropertyPathIsInvalid(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate(null, new PlainDateAfter());
    }

    public function testNullValue(): void
    {
        $this->validator->validate(null, new PlainDateAfter('date'));

        self::assertNoViolation();
    }

    public function testValidWhenDateIsAfter(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateAfter('2000-01-01'),
        );

        self::assertNoViolation();
    }

    public function testViolationWhenDateIsNotAfter(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfter('2000-01-01'),
        );

        $this->buildViolation('This date must be after {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateAfter::PLAIN_DATE_AFTER_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenDateEqualsAfterLimit(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfter('2000-01-01'),
        );

        $this->buildViolation('This date must be after {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateAfter::PLAIN_DATE_AFTER_ERROR)
            ->assertRaised();
    }

    public function testAcceptsRelativeDate(): void
    {
        $this->validator->validate(
            PlainDate::create('2999-01-01'),
            new PlainDateAfter('today'),
        );

        self::assertNoViolation();
    }

    public function testCustomMessage(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfter('2000-01-02', 'plain_date.after'),
        );

        $this->buildViolation('plain_date.after')
            ->setParameter('{{ limit }}', '2000-01-02')
            ->setCode(PlainDateAfter::PLAIN_DATE_AFTER_ERROR)
            ->assertRaised();
    }

    public function testValidWhenDateAndPropertyPathAfter(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1970-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1975-01-01'),
            new PlainDateAfter('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testInvalidWhenDateAndPropertyPathBefore(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1980-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1969-01-01'),
            new PlainDateAfter('+1 year', propertyPath: 'someField'),
        );

        $this->buildViolation('This date must be after {{ limit }}.')
            ->setParameter('{{ limit }}', '1981-01-01')
            ->setCode(PlainDateAfter::PLAIN_DATE_AFTER_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): PlainDateAfterValidator
    {
        return new PlainDateAfterValidator();
    }
}
