<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Constraint;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_array;

class ValidContentTreeNodeCountValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ValidContentTreeNodeCount) {
            throw new UnexpectedTypeException($constraint, ValidContentTreeNodeCount::class);
        }

        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $nodeCount = 0;
        $this->countNodes($value, $constraint, '', $nodeCount);
    }

    /** @param array<array-key, mixed> $nodes */
    private function countNodes(
        array $nodes,
        ValidContentTreeNodeCount $constraint,
        string $pathPrefix,
        int &$nodeCount,
    ): void {
        foreach ($nodes as $index => $node) {
            if (! $node instanceof SubjectContentNode) {
                throw new UnexpectedValueException($node, SubjectContentNode::class);
            }

            if ($nodeCount > $constraint->max) {
                return;
            }

            $nodeCount++;
            $nodePath = $pathPrefix . '[' . $index . ']';

            if ($nodeCount > $constraint->max) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($nodePath)
                    ->addViolation();

                return;
            }

            if ($node->children === []) {
                continue;
            }

            $this->countNodes($node->children, $constraint, $nodePath . '.children', $nodeCount);
        }
    }
}
