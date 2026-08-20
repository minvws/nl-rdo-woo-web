<?php

declare(strict_types=1);

namespace Shared\Validator\MarkdownAllowedNodeTypes;

use League\CommonMark\Node\Node;
use ReflectionClass;
use Shared\Domain\Content\Markdown\MarkdownConverter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

use function is_string;

class MarkdownAllowedNodeTypesValidator extends ConstraintValidator
{
    public function __construct(
        private readonly MarkdownConverter $markdownConverter,
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

        $walker = $this->markdownConverter->getParser()->parse($value)->walker();

        while (($event = $walker->next()) !== null) {
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
        foreach ($allowedNodeTypes as $allowedClass) {
            if ($node instanceof $allowedClass) {
                return true;
            }
        }

        return false;
    }
}
