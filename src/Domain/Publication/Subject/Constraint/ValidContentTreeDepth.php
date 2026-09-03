<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Constraint;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ValidContentTreeDepth extends Constraint
{
    public string $message = 'subject.content_tree.max_depth_exceeded';

    public function __construct(
        public int $max = 3,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
