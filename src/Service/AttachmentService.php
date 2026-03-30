<?php

declare(strict_types=1);

namespace Shared\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class AttachmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param list<AbstractAttachment> $attachments
     *
     * @throws ValidationFailedException
     */
    public function validate(array $attachments): void
    {
        $errors = $this->validator->validate($attachments);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($attachments, $errors);
        }
    }

    /**
     * @param array<array-key,AbstractAttachment> $attachements
     */
    public function refreshAttachments(array $attachements): void
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        foreach ($attachements as $attachment) {
            if ($this->entityManager->contains($attachment) && ! $unitOfWork->isScheduledForInsert($attachment)) {
                $this->entityManager->refresh($attachment);
            }
        }
    }
}
