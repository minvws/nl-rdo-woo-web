<?php

declare(strict_types=1);

namespace Shared\Validator\MarkdownAllowedNodeTypes;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

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
        string|array $allowedNodeTypes = [],
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        if (is_string($allowedNodeTypes)) {
            $allowedNodeTypes = [$allowedNodeTypes];
        }

        parent::__construct(
            options: ['allowedNodeTypes' => $allowedNodeTypes],
            groups: $groups,
            payload: $payload,
        );

        if ($message !== null) {
            $this->message = $message;
        }
    }

    #[Override]
    public function getDefaultOption(): string
    {
        return 'allowedNodeTypes';
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function getRequiredOptions(): array
    {
        return ['allowedNodeTypes'];
    }

    #[Override]
    public function validatedBy(): string
    {
        return MarkdownAllowedNodeTypesValidator::class;
    }
}
