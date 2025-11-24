<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DateFromConstraint extends Constraint
{
    public string $message = 'date_to_before_date_from';
    public string $mode = 'strict';

    public function __construct(?string $mode = null, ?string $message = null, ?array $groups = null, $payload = null)
    {
        parent::__construct([], $groups, $payload);

        $this->mode = $mode ?? $this->mode;
        $this->message = $message ?? $this->message;
    }
}
