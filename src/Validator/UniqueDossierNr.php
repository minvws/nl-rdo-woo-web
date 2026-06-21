<?php

declare(strict_types=1);

namespace Shared\Validator;

use Attribute;
use Override;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class UniqueDossierNr extends Constraint
{
    public function __construct(
        public string $documentPrefix,
        public ?Uuid $excludeId = null,
        public string $message = 'dossier.dossier_nr_not_unique',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }

    #[Override]
    public function validatedBy(): string
    {
        return UniqueDossierNrValidator::class;
    }
}
