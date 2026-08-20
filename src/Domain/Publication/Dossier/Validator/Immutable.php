<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Immutable extends Constraint
{
    public const string ERROR_CODE = '1f16fb45-c82d-63ea-bbd8-0d6e0b619a25';

    /**
     * @param array<array-key, string>|null $groups
     */
    public function __construct(
        public string $message = 'validation.immutable',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }

    #[Override]
    public function validatedBy(): string
    {
        return ImmutableValidator::class;
    }
}
