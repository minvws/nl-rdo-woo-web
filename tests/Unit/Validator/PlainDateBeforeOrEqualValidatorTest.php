<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqualValidator;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
class PlainDateBeforeOrEqualValidatorTest extends ConstraintValidatorTestCase
{
    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateBeforeOrEqual('2000-01-01'));
    }

    public function testThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateAfterOrEqual('2000-01-01'));
    }

    public function testNoDateOrPropertyPathIsInvalid(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate(null, new PlainDateBeforeOrEqual());
    }

    public function testNullValue(): void
    {
        $this->validator->validate(null, new PlainDateBeforeOrEqual('date'));

        self::assertNoViolation();
    }

    public function testValidWhenDateIsBefore(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateBeforeOrEqual('2000-01-02'),
        );

        self::assertNoViolation();
    }

    public function testValidWhenDateIsEqual(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateBeforeOrEqual('2000-01-01'),
        );

        self::assertNoViolation();
    }

    public function testViolationWhenDateIsNotBefore(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateBeforeOrEqual('2000-01-01'),
        );

        $this->buildViolation('This date must be before or equal to {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    public function testAcceptsRelativeDate(): void
    {
        $this->validator->validate(
            PlainDate::create('1970-01-01'),
            new PlainDateBeforeOrEqual('today'),
        );

        self::assertNoViolation();
    }

    public function testCustomMessage(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateBeforeOrEqual('2000-01-01', 'plain_date.before_or_equal'),
        );

        $this->buildViolation('plain_date.before_or_equal')
            ->setParameter('{{ limit }}', '2000-01-01')
            ->setCode(PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    public function testValidWhenDateAndPropertyPathBefore(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1980-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1975-01-01'),
            new PlainDateBeforeOrEqual('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testValidWhenDateAndPropertyPathEqual(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1980-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1981-01-01'),
            new PlainDateBeforeOrEqual('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testInvalidWhenDateAndPropertyPathAfter(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1980-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1985-01-01'),
            new PlainDateBeforeOrEqual('+1 year', propertyPath: 'someField'),
        );

        $this->buildViolation('This date must be before or equal to {{ limit }}.')
            ->setParameter('{{ limit }}', '1981-01-01')
            ->setCode(PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): PlainDateBeforeOrEqualValidator
    {
        return new PlainDateBeforeOrEqualValidator();
    }
}
