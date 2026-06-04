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
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $documentPrefix,
        public ?Uuid $excludeId = null,
        public string $message = 'dossier.dossier_nr_not_unique',
        array $options = [],
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    #[Override]
    public function validatedBy(): string
    {
        return UniqueDossierNrValidator::class;
    }
}
