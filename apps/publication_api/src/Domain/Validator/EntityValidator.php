<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;

readonly class EntityValidator
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function throwExceptionIfNotValid(object $entity): void
    {
        $violations = $this->validator->validate($entity);

        if (count($violations) === 0) {
            return;
        }

        $uow = $this->entityManager->getUnitOfWork();
        if ($this->entityManager->contains($entity) && ! $uow->isScheduledForInsert($entity)) {
            $this->entityManager->refresh($entity);
        }

        throw new ValidationException($violations);
    }
}
