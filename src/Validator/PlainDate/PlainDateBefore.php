<?php

declare(strict_types=1);

namespace Shared\Validator\PlainDate;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class PlainDateBefore extends PlainDateConstraint
{
    public const string PLAIN_DATE_BEFORE_ERROR = '1f11e193-3794-689a-8cb8-47c22cc30432';

    public function __construct(
        public ?string $date = null,
        public string $message = 'This date must be before {{ limit }}.',
        public ?string $propertyPath = null,
        public ?array $groups = null,
        public mixed $payload = null,
    ) {
        parent::__construct($date, $message, $propertyPath, $groups, $payload);
    }
}
