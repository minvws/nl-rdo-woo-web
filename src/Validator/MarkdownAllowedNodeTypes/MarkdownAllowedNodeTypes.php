<?php

declare(strict_types=1);

namespace Shared\Validator\MarkdownAllowedNodeTypes;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class MarkdownAllowedNodeTypes extends Constraint
{
    public string $message = 'The Markdown contains an element that is not allowed ({{ type }}).';

    /** @var list<class-string> */
    public array $allowedNodeTypes = [];

    /**
     * @param class-string|list<class-string> $allowedNodeTypes
     */
    public function __construct(
        string|array $allowedNodeTypes,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (is_string($allowedNodeTypes)) {
            $allowedNodeTypes = [$allowedNodeTypes];
        }

        if ($allowedNodeTypes === []) {
            throw new ConstraintDefinitionException('Option "allowedNodeTypes" is required and cannot be empty');
        }

        parent::__construct(
            groups: $groups,
            payload: $payload,
        );

        $this->allowedNodeTypes = $allowedNodeTypes;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function validatedBy(): string
    {
        return MarkdownAllowedNodeTypesValidator::class;
    }
}
