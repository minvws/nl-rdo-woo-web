<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class NoIncompleteAttachments extends Constraint
{
    public string $message = 'dossier.incomplete_attachments';

    public function __construct(mixed $options = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct($options, $groups, $payload);
    }

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
