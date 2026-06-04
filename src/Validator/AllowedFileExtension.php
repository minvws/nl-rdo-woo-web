<?php

declare(strict_types=1);

namespace Shared\Validator;

use Attribute;
use Override;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AllowedFileExtension extends Constraint
{
    public const string INVALID_EXTENSION_ERROR = 'b3e2f1a0-9c4d-4e6b-8f1a-2d3c5e7a9b0f';

    public string $message = 'The file extension "{{ extension }}" is not allowed. Allowed extensions are: {{ allowed }}.';

    public function __construct(
        public UploadGroupId $uploadGroupId,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    #[Override]
    public function validatedBy(): string
    {
        return AllowedFileExtensionValidator::class;
    }
}
