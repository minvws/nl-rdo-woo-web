<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function array_column;

readonly class AttachmentService
{
    public function __construct(
        private ValidatorInterface $validatorInterface,
    ) {
    }

    /**
     * @param list<AbstractAttachment> $attachments
     *
     * @throws ValidationFailedException
     */
    public function validate(array $attachments): void
    {
        $validationGroups = array_column(DossierValidationGroup::cases(), 'value');
        $errors = $this->validatorInterface->validate($attachments, groups: $validationGroups);
        if ($errors->count() > 0) {
            throw new ValidationFailedException($attachments, $errors);
        }
    }
}
