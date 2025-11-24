<?php

declare(strict_types=1);

namespace Shared\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class EntityExists extends Constraint
{
    public const string ENTITY_EXISTS_ERROR = '1f0c13f5-7135-670a-8c9b-c9120dd3a68b';

    /**
     * @param class-string  $entityClass
     * @param array<mixed>  $options
     * @param array<string> $groups
     */
    public function __construct(
        public string $entityClass,
        public string $name,
        public string $field = 'id',
        public string $message = 'The referenced entity "{{ name }}" could not be found',
        array $options = [],
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return EntityExistsValidator::class;
    }
}
