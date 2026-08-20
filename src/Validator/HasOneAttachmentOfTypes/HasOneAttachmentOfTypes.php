<?php

declare(strict_types=1);

namespace Shared\Validator\HasOneAttachmentOfTypes;

use Attribute;
use Override;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
class HasOneAttachmentOfTypes extends Constraint
{
    /**
     * @param list<AttachmentType> $types
     * @param list<string> $errorPaths
     */
    public function __construct(
        public readonly array $types,
        public readonly string $property = 'attachments',
        public readonly array $errorPaths = [],
        public string $message = 'has_one_attachment_of_types.none',
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options, $groups, $payload);
    }

    #[Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
