<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Shared\Validator\PlainDate\PlainDateAfter;
use Shared\Validator\PlainDate\PlainDateBefore;
use Shared\Validator\PlainDate\PlainDateBeforeValidator;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
class PlainDateBeforeValidatorTest extends ConstraintValidatorTestCase
{
    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateBefore('2000-01-01'));
    }

    public function testThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateAfter('2000-01-01'));
    }

    public function testNoDateOrPropertyPathIsInvalid(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate(null, new PlainDateBefore());
    }

    public function testNullValue(): void
    {
        $this->validator->validate(null, new PlainDateBefore('date'));

        self::assertNoViolation();
    }

    public function testValidWhenDateIsBefore(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateBefore('2000-01-02'),
        );

        self::assertNoViolation();
    }

    public function testViolationWhenDateIsNotBefore(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateBefore('2000-01-01'),
        );

        $this->buildViolation('This date must be before {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateBefore::PLAIN_DATE_BEFORE_ERROR)
            ->assertRaised();
    }

    public function testViolationWhenDateEqualsBeforeLimit(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateBefore('2000-01-01'),
        );

        $this->buildViolation('This date must be before {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateBefore::PLAIN_DATE_BEFORE_ERROR)
            ->assertRaised();
    }

    public function testAcceptsRelativeDate(): void
    {
        $this->validator->validate(
            PlainDate::create('1970-01-01'),
            new PlainDateBefore('today'),
        );

        self::assertNoViolation();
    }

    public function testCustomMessage(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateBefore('2000-01-01', 'plain_date.before'),
        );

        $this->buildViolation('plain_date.before')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateBefore::PLAIN_DATE_BEFORE_ERROR)
            ->assertRaised();
    }

    public function testValidWhenDateAndPropertyPathBefore(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1980-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1975-01-01'),
            new PlainDateBefore('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testInvalidWhenDateAndPropertyPathAfter(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1970-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1975-01-01'),
            new PlainDateBefore('+1 year', propertyPath: 'someField'),
        );

        $this->buildViolation('This date must be before {{ limit }}.')
            ->setParameter('{{ limit }}', '1971-01-01')
            ->setCode(PlainDateBefore::PLAIN_DATE_BEFORE_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): PlainDateBeforeValidator
    {
        return new PlainDateBeforeValidator();
    }
}
