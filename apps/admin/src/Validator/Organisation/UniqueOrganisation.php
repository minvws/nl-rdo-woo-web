<?php

declare(strict_types=1);

namespace Admin\Validator\Organisation;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class UniqueOrganisation extends Constraint
{
    public function __construct(
        public string $nameMessage = 'This organisation already exists.',
        public string $prefixMessage = 'organisation.prefix_already_exists',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
