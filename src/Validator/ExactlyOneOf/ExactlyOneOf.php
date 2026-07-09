<?php

declare(strict_types=1);

namespace Shared\Validator\ExactlyOneOf;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class ExactlyOneOf extends Constraint
{
    /**
     * @param list<string> $properties
     * @param list<string> $errorPaths
     */
    public function __construct(
        public readonly array $properties,
        public readonly array $errorPaths = [],
        public string $noneMessage = 'exactly_one_of.none',
        public string $multipleMessage = 'exactly_one_of.multiple',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
