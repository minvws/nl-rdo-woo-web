<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class PlainDateAfter extends PlainDateConstraint
{
    public const string PLAIN_DATE_AFTER_ERROR = '1f11e193-3794-69b2-8d72-47c22cc30432';

    public function __construct(
        public ?string $date = null,
        public string $message = 'This date must be after {{ limit }}.',
        public ?string $propertyPath = null,
        public ?array $groups = null,
        public mixed $payload = null,
    ) {
        parent::__construct($date, $message, $propertyPath, $groups, $payload);
    }
}
