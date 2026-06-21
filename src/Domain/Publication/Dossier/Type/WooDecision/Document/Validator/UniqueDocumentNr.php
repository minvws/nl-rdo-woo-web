<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class UniqueDocumentNr extends Constraint
{
    public const string NOT_UNIQUE_ERROR = 'b5c8f3e2-2d1a-4f7c-9e4b-6a3c8d5e1f9b';

    public function __construct(
        public string $message = 'document.document_nr_not_unique',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    #[Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
