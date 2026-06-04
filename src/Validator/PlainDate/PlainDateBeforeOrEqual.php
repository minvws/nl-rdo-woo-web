<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class PlainDateBeforeOrEqual extends PlainDateConstraint
{
    public const string PLAIN_DATE_BEFORE_OR_EQUAL_ERROR = '1f11e193-3794-6926-be0e-47c22cc30432';

    public function __construct(
        public ?string $date = null,
        public string $message = 'This date must be before or equal to {{ limit }}.',
        public ?string $propertyPath = null,
        public ?array $groups = null,
        public mixed $payload = null,
    ) {
        parent::__construct($date, $message, $propertyPath, $groups, $payload);
    }
}
