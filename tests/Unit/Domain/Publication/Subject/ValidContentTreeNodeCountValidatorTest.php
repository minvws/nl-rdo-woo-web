<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Subject;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeNodeCount;
use Shared\Domain\Publication\Subject\Constraint\ValidContentTreeNodeCountValidator;
use Shared\Domain\Publication\Subject\SubjectContentNode;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidContentTreeNodeCountValidatorTest extends UnitTestCase
{
    private ExecutionContextInterface&MockInterface $context;
    private ValidContentTreeNodeCountValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Mockery::mock(ExecutionContextInterface::class);
        $this->validator = new ValidContentTreeNodeCountValidator();
    }

    public function test100NodesIsValid(): void
    {
        $nodes = [];
        for ($i = 0; $i < 100; $i++) {
            $nodes[] = new SubjectContentNode('Node ' . $i, 'body');
        }

        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate($nodes, new ValidContentTreeNodeCount(max: 100));
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

        $this->validate($nodes, new ValidContentTreeNodeCount(max: 100));
    }

    public function testNodeCountAcrossNestedChildrenAddsViolation(): void
    {
        $children = [];
        for ($i = 0; $i < 100; $i++) {
            $children[] = new SubjectContentNode('Child ' . $i, 'body');
        }
        $root = new SubjectContentNode('Root', 'body', $children);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.too_many_nodes')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validate([$root], new ValidContentTreeNodeCount(max: 100));
    }

    public function testMultipleExcessNestedNodesAddOnlyOneViolation(): void
    {
        $children = [];
        for ($i = 0; $i < 20; $i++) {
            $children[] = new SubjectContentNode('Child ' . $i, 'body');
        }

        $roots = [];
        for ($i = 0; $i < 10; $i++) {
            $roots[] = new SubjectContentNode('Root ' . $i, 'body', $children);
        }

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $this->context->expects('buildViolation')
            ->with('subject.content_tree.too_many_nodes')
            ->andReturn($builder);
        $builder->expects('atPath')->andReturn($builder);
        $builder->expects('addViolation');

        $this->validate($roots, new ValidContentTreeNodeCount(max: 100));
    }

    public function testNonArrayValueThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validate('not-an-array', new ValidContentTreeNodeCount(max: 100));
    }

    public function testNonSubjectContentNodeItemThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validate(['not-a-node'], new ValidContentTreeNodeCount(max: 100));
    }

    public function testNullValueIsIgnored(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate(null, new ValidContentTreeNodeCount(max: 100));
    }

    public function testEmptyTreeIsValid(): void
    {
        $this->context->shouldNotHaveReceived('buildViolation');

        $this->validate([], new ValidContentTreeNodeCount(max: 100));
    }

    private function validate(mixed $value, Constraint $constraint): void
    {
        $this->validator->validateInContext($value, $constraint, $this->context);
    }
}
