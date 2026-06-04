<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\Validator\PlainDate\PlainDateAfterOrEqualValidator;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

#[AllowMockObjectsWithoutExpectations]
class PlainDateAfterOrEqualValidatorTest extends ConstraintValidatorTestCase
{
    public function testThrowsOnInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateAfterOrEqual('2000-01-01'));
    }

    public function testThrowsOnInvalidType(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('not-a-plain-date', new PlainDateBeforeOrEqual('2000-01-01'));
    }

    public function testNoDateOrPropertyPathIsInvalid(): void
    {
        $this->expectException(ConstraintDefinitionException::class);

        $this->validator->validate(null, new PlainDateAfterOrEqual());
    }

    public function testNullValue(): void
    {
        $this->validator->validate(null, new PlainDateAfterOrEqual('date'));

        self::assertNoViolation();
    }

    public function testValidWhenDateIsAfter(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-02'),
            new PlainDateAfterOrEqual('2000-01-01'),
        );

        self::assertNoViolation();
    }

    public function testValidWhenDateIsEqual(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfterOrEqual('2000-01-01'),
        );

        self::assertNoViolation();
    }

    public function testViolationWhenDateIsNotAfter(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfterOrEqual('2000-01-02'),
        );

        $this->buildViolation('This date must be after or equal to {{ limit }}.')
            ->setParameter('{{ limit }}', '2000-01-02')
            ->setCode(PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    public function testAcceptsRelativeDate(): void
    {
        $this->validator->validate(
            PlainDate::create('2999-01-01'),
            new PlainDateAfterOrEqual('today'),
        );

        self::assertNoViolation();
    }

    public function testCustomMessage(): void
    {
        $this->validator->validate(
            PlainDate::create('2000-01-01'),
            new PlainDateAfterOrEqual('2000-01-02', 'plain_date.after_or_equal'),
        );

        $this->buildViolation('plain_date.after_or_equal')
            ->setParameter('{{ limit }}', '2000-01-02')
            ->setCode(PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    public function testValidWhenDateAndPropertyPathAfter(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1970-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1975-01-01'),
            new PlainDateAfterOrEqual('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testValidWhenDateAndPropertyPathEqual(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1970-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1971-01-01'),
            new PlainDateAfterOrEqual('+1 year', propertyPath: 'someField'),
        );

        self::assertNoViolation();
    }

    public function testInvalidWhenDateAndPropertyPathBefore(): void
    {
        $object = new stdClass();
        $object->someField = PlainDate::create('1970-01-01');
        $this->setObject($object);

        $this->validator->validate(
            PlainDate::create('1969-01-01'),
            new PlainDateAfterOrEqual('+1 year', propertyPath: 'someField'),
        );

        $this->buildViolation('This date must be after or equal to {{ limit }}.')
            ->setParameter('{{ limit }}', '1971-01-01')
            ->setCode(PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR)
            ->assertRaised();
    }

    protected function createValidator(): PlainDateAfterOrEqualValidator
    {
        return new PlainDateAfterOrEqualValidator();
    }
}
