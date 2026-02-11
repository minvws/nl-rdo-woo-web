<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Validator\DateFromConstraint;
use Shared\Domain\Publication\Dossier\Validator\DateFromConstraintValidator;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class DateFromConstraintValidatorTest extends UnitTestCase
{
    public function testValidateThrowsExceptionForUnsupportedConstraint(): void
    {
        $validator = new DateFromConstraintValidator();

        $this->expectException(UnexpectedTypeException::class);
        $validator->validate('foo', new NotNull());
    }

    public function testValidateAddsNoErrorForEmptyValue(): void
    {
        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotHaveBeenCalled();

        $validator = new DateFromConstraintValidator();
        $validator->initialize($context);

        $validator->validate(null, new DateFromConstraint());
    }

    public function testValidateThrowsExceptionForMissingDossier(): void
    {
        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('getObject')->andReturn(new stdClass());

        $validator = new DateFromConstraintValidator();
        $validator->initialize($context);

        $this->expectException(UnexpectedValueException::class);
        $validator->validate('foo', new DateFromConstraint());
    }

    public function testValidateAddsViolationForDateTooFarBeforeCreationDate(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('hasCreatedAt')->andReturnTrue();
        $dossier->expects('getCreatedAt')->andReturn(new DateTimeImmutable('2020-08-21'));
        $dossier->expects('getDateFrom')->andReturn(new DateTimeImmutable('2002-10-04'));

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('getObject')->andReturn($dossier);
        $context->expects('buildViolation->addViolation');

        $validator = new DateFromConstraintValidator();
        $validator->initialize($context);

        $validator->validate('foo', new DateFromConstraint());
    }

    public function testValidateAddsNoViolationForDateWithin10YearsFromCreationDate(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('hasCreatedAt')->andReturnTrue();
        $dossier->expects('getCreatedAt')->andReturn(new DateTimeImmutable('2020-08-21'));
        $dossier->expects('getDateFrom')->andReturn(new DateTimeImmutable('2012-12-05'));

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('getObject')->andReturn($dossier);

        $validator = new DateFromConstraintValidator();
        $validator->initialize($context);

        $validator->validate('foo', new DateFromConstraint());
    }

    public function testValidateAddsNoViolationForDateWithin10YearsFromNowIfCreationDateIsMissing(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2020, 8, 21));

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('hasCreatedAt')->andReturnFalse();
        $dossier->expects('getDateFrom')->andReturn(new DateTimeImmutable('2012-12-05'));

        /** @var ExecutionContextInterface&MockInterface $context */
        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('getObject')->andReturn($dossier);

        $validator = new DateFromConstraintValidator();
        $validator->initialize($context);

        $validator->validate('foo', new DateFromConstraint());
    }
}
