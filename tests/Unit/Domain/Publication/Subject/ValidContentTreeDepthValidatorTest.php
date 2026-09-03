<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepth;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeDepthValidator;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use function str_repeat;

class ValidContentTreeDepthValidatorTest extends UnitTestCase
{
    private ExecutionContextInterface&MockInterface $context;
    private ValidContentTreeDepthValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Mockery::mock(ExecutionContextInterface::class);
        $this->validator = new ValidContentTreeDepthValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidThreeLevelTree(): void
    {
        $level3 = new SubjectContentNode('L3', 'body');
        $level2 = new SubjectContentNode('L2', 'body', [$level3]);
        $level1 = new SubjectContentNode('L1', 'body', [$level2]);

        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validator->validate([$level1], new ValidContentTreeDepth(max: 3));
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

        $this->validator->validate([$level1], new ValidContentTreeDepth(max: 3));
    }

    public function testBlankTitleAddsViolation(): void
    {
        $node = new SubjectContentNode('', 'body');

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.blank_title')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validator->validate([$node], new ValidContentTreeDepth(max: 3));
    }

    public function testBodyLengthOver10000AddsViolation(): void
    {
        $node = new SubjectContentNode('Title', str_repeat('x', 10001));

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.body_too_long')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validator->validate([$node], new ValidContentTreeDepth(max: 3));
    }

    public function test101NodesAddsViolation(): void
    {
        $nodes = [];
        for ($i = 0; $i < 101; $i++) {
            $nodes[] = new SubjectContentNode('Node ' . $i, 'body');
        }

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.too_many_nodes')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validator->validate($nodes, new ValidContentTreeDepth(max: 3));
    }

    public function testNonArrayValueThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-an-array', new ValidContentTreeDepth(max: 3));
    }

    public function testNonSubjectContentNodeItemThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(['not-a-node'], new ValidContentTreeDepth(max: 3));
    }

    public function testWhitespaceOnlyTitleAddsViolation(): void
    {
        $node = new SubjectContentNode('   ', 'body');

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.blank_title')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validator->validate([$node], new ValidContentTreeDepth(max: 3));
    }

    public function testNullValueIsIgnored(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validator->validate(null, new ValidContentTreeDepth(max: 3));
    }

    public function testEmptyTreeIsValid(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validator->validate([], new ValidContentTreeDepth(max: 3));
    }
}
