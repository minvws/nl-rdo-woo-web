<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class AttachmentService
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param array<array-key,AbstractAttachment> $attachments
     *
     * @throws ValidationFailedException
     */
    public function validate(array $attachments): void
    {
        $validationGroups = \array_column(DossierValidationGroup::cases(), 'value');
        foreach ($attachments as $attachment) {
            $errors = $this->validator->validate($attachment, groups: $validationGroups);

            if ($errors->count() > 0) {
                throw new ValidationFailedException($attachment, $errors);
            }
        }
    }
}
