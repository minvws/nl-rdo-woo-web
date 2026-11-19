<?php

declare(strict_types=1);

namespace Shared\Validator\MarkdownAllowedNodeTypes;

use League\CommonMark\Node\Node;
use League\CommonMark\Node\NodeWalkerEvent;
use ReflectionClass;
use Shared\Domain\Content\Markdown\MarkdownFormatter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function array_any;
use function is_string;

class MarkdownAllowedNodeTypesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly MarkdownFormatter $markdownFormatter,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof MarkdownAllowedNodeTypes) {
            throw new UnexpectedTypeException($constraint, MarkdownAllowedNodeTypes::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $walker = $this->markdownFormatter->parse($value)->walker();

        while (($event = $walker->next()) instanceof NodeWalkerEvent) {
            if (! $event->isEntering()) {
                continue;
            }

            $node = $event->getNode();

            if ($this->isAllowedNode($node, $constraint->allowedNodeTypes)) {
                continue;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ type }}', new ReflectionClass($node)->getShortName())
                ->addViolation();

            return;
        }
    }

    /**
     * @param list<class-string> $allowedNodeTypes
     */
    private function isAllowedNode(Node $node, array $allowedNodeTypes): bool
    {
        return array_any($allowedNodeTypes, static fn ($allowedClass) => $node instanceof $allowedClass);
    }
}
