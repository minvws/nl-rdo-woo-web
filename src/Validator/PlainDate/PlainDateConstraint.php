<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\LogicException;

use function class_exists;

abstract class PlainDateConstraint extends Constraint
{
    public function __construct(
        public ?string $date = null,
        public string $message = 'This date must be after or equal to {{ limit }}.',
        public ?string $propertyPath = null,
        public ?array $groups = null,
        public mixed $payload = null,
    ) {
        if ($this->date === null && $this->propertyPath === null) {
            throw new ConstraintDefinitionException('Set either date or propertyPath (or both)');
        }

        if ($this->propertyPath !== null && ! class_exists(PropertyAccess::class)) {
            throw new LogicException('Property access failed');
        }

        parent::__construct(null, $groups, $payload);
    }
}
