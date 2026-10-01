<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepth;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepthValidator;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidContentTreeDepthValidatorTest extends UnitTestCase
{
    private ExecutionContextInterface&MockInterface $context;
    private ValidContentTreeDepthValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Mockery::mock(ExecutionContextInterface::class);
        $this->validator = new ValidContentTreeDepthValidator();
    }

    public function testValidThreeLevelTree(): void
    {
        $level3 = new SubjectContentNode('L3', 'body');
        $level2 = new SubjectContentNode('L2', 'body', [$level3]);
        $level1 = new SubjectContentNode('L1', 'body', [$level2]);

        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate([$level1], new ValidContentTreeDepth(max: 3));
    }

    public function testInvalidFourLevelTreeAddsViolation(): void
    {
        $level4 = new SubjectContentNode('L4', 'body');
        $level3 = new SubjectContentNode('L3', 'body', [$level4]);
        $level2 = new SubjectContentNode('L2', 'body', [$level3]);
        $level1 = new SubjectContentNode('L1', 'body', [$level2]);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.max_depth_exceeded')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validate([$level1], new ValidContentTreeDepth(max: 3));
    }

    public function testNonArrayValueThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validate('not-an-array', new ValidContentTreeDepth(max: 3));
    }

    public function testNonSubjectContentNodeItemThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validate(['not-a-node'], new ValidContentTreeDepth(max: 3));
    }

    public function testNullValueIsIgnored(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate(null, new ValidContentTreeDepth(max: 3));
    }

    public function testEmptyTreeIsValid(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate([], new ValidContentTreeDepth(max: 3));
    }

    private function validate(mixed $value, Constraint $constraint): void
    {
        $this->validator->validateInContext($value, $constraint, $this->context);
    }
}
