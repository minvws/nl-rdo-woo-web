<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Validator\ExactlyOneOf;

use Mockery;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Validator\ExactlyOneOf\ExactlyOneOf;
use Shared\Validator\ExactlyOneOf\ExactlyOneOfValidator;
use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ExactlyOneOfValidatorTest extends UnitTestCase
{
    public function testThrowsUnexpectedTypeExceptionForWrongConstraint(): void
    {
        $validator = new ExactlyOneOfValidator();
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(UnexpectedTypeException::class);
        $validator->validate(new stdClass(), Mockery::mock(Constraint::class));
    }

    public function testThrowsUnexpectedValueExceptionForNonObject(): void
    {
        $validator = new ExactlyOneOfValidator();
        $validator->initialize(Mockery::mock(ExecutionContextInterface::class));

        $this->expectException(UnexpectedValueException::class);
        $validator->validate('not an object', new ExactlyOneOf(['foo', 'bar']));
    }

    public function testNoViolationWhenExactlyOnePropertyIsNotNull(): void
    {
        $object = new stdClass();
        $object->foo = $this->getFaker()->word();
        $object->bar = null;

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar']));
    }

    public function testViolationWhenNoPropertiesAreNotNull(): void
    {
        $object = new stdClass();
        $object->foo = null;
        $object->bar = null;

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation')
            ->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('exactly_one_of.none')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar']));
    }

    public function testViolationWhenMultiplePropertiesAreNotNull(): void
    {
        $object = new stdClass();
        $object->foo = $this->getFaker()->word();
        $object->bar = $this->getFaker()->word();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation')
            ->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('exactly_one_of.multiple')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar']));
    }

    public function testViolationIsAddedToEachErrorPathWhenNoPropertiesAreNotNull(): void
    {
        $object = new stdClass();
        $object->foo = null;
        $object->bar = null;

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('atPath')
            ->with('foo')
            ->once()
            ->andReturn($builder);
        $builder->expects('atPath')
            ->with('bar')
            ->once()
            ->andReturn($builder);
        $builder->expects('addViolation')
            ->twice();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->twice()
            ->with('exactly_one_of.none')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar'], errorPaths: ['foo', 'bar']));
    }

    public function testViolationIsAddedToEachErrorPathWhenMultiplePropertiesAreNotNull(): void
    {
        $object = new stdClass();
        $object->foo = $this->getFaker()->word();
        $object->bar = $this->getFaker()->word();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('atPath')
            ->with('foo')
            ->once()
            ->andReturn($builder);
        $builder->expects('atPath')
            ->with('bar')
            ->once()
            ->andReturn($builder);
        $builder->expects('addViolation')
            ->twice();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->twice()
            ->with('exactly_one_of.multiple')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar'], errorPaths: ['foo', 'bar']));
    }

    public function testCustomMessages(): void
    {
        $object = new stdClass();
        $object->foo = null;
        $object->bar = null;

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation')
            ->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('custom.none.message')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar'], noneMessage: 'custom.none.message'));
    }

    public function testCustomMultipleMessage(): void
    {
        $object = new stdClass();
        $object->foo = $this->getFaker()->word();
        $object->bar = $this->getFaker()->word();

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('addViolation')
            ->once();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')
            ->once()
            ->with('custom.multiple.message')
            ->andReturn($builder);

        $validator = new ExactlyOneOfValidator();
        $validator->initialize($context);
        $validator->validate($object, new ExactlyOneOf(['foo', 'bar'], multipleMessage: 'custom.multiple.message'));
    }
}
