<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Constraint;

use Shared\Domain\Publication\Subject\SubjectContentNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_array;
use function mb_strlen;
use function trim;

class ValidContentTreeDepthValidator extends ConstraintValidator
{
    private const int MAX_NODE_COUNT = 100;
    private const int MAX_BODY_LENGTH = 10000;
    private const string MESSAGE_TOO_MANY_NODES = 'subject.content_tree.too_many_nodes';

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

        $nodeCount = 0;
        $this->validateNodes($value, $constraint->max, 1, '', $constraint, $nodeCount);
    }

    /** @param array<array-key, mixed> $nodes */
    private function validateNodes(
        array $nodes,
        int $max,
        int $currentLevel,
        string $pathPrefix,
        ValidContentTreeDepth $constraint,
        int &$nodeCount,
    ): void {
        foreach ($nodes as $index => $node) {
            if (! $node instanceof SubjectContentNode) {
                throw new UnexpectedValueException($node, SubjectContentNode::class);
            }

            $nodeCount++;
            $nodePath = $pathPrefix . '[' . $index . ']';

            if ($nodeCount > self::MAX_NODE_COUNT) {
                $this->context->buildViolation(self::MESSAGE_TOO_MANY_NODES)
                    ->atPath($nodePath)
                    ->addViolation();

                return;
            }

            if (trim($node->title) === '') {
                $this->context->buildViolation('subject.content_tree.blank_title')
                    ->atPath($nodePath . '.title')
                    ->addViolation();
            }

            if (mb_strlen($node->body) > self::MAX_BODY_LENGTH) {
                $this->context->buildViolation('subject.content_tree.body_too_long')
                    ->atPath($nodePath . '.body')
                    ->addViolation();
            }

            if ($node->children !== []) {
                if ($currentLevel >= $max) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath($nodePath . '.children')
                        ->addViolation();
                } else {
                    $this->validateNodes($node->children, $max, $currentLevel + 1, $nodePath . '.children', $constraint, $nodeCount);
                }
            }
        }
    }
}
