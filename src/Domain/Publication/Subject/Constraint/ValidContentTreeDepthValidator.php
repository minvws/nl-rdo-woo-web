<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Constraint;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_array;

class ValidContentTreeDepthValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ValidContentTreeDepth) {
            throw new UnexpectedTypeException($constraint, ValidContentTreeDepth::class);
        }

        if ($value === null) {
            return;
        }

        if (! is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $this->validateNodes($value, $constraint->max, 1, '', $constraint);
    }

    /** @param array<array-key, mixed> $nodes */
    private function validateNodes(
        array $nodes,
        int $max,
        int $currentLevel,
        string $pathPrefix,
        ValidContentTreeDepth $constraint,
    ): void {
        foreach ($nodes as $index => $node) {
            if (! $node instanceof SubjectContentNode) {
                throw new UnexpectedValueException($node, SubjectContentNode::class);
            }

            $nodePath = $pathPrefix . '[' . $index . ']';

            if ($node->children === []) {
                continue;
            }

            if ($currentLevel >= $max) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($nodePath . '.children')
                    ->addViolation();
            } else {
                $this->validateNodes($node->children, $max, $currentLevel + 1, $nodePath . '.children', $constraint);
            }
        }
    }
}
